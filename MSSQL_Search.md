# MSSQL Search over several tables using views

# Introduction

Sometimes it's necessary to perform a full-text search over several joined tables. The example I'll be using here is a search for forum posts - where I'm also interested in the title of the thread and names of the post authors.

As a refresher, the abridged data model for the SilverStripe forum module is:

	ForumThread:
		Title: Varchar
		Posts: (has_many)Post
	Post:
		Content: Text
		Thread: (has_one)ForumThread
		Author: (has_one)Member
	Member:
		Firstname: Varchar
		Surname: Varchar

The specific columns I want to search in are: ForumThread.Title, Post.Content, Member.FirstName and Member.Surname.

# Solution

MSSQL does not come with a capablity to add a full-text index on several tables at the same time. We need to construct a view that will include the data we need.

## View and index

I've chosen to create the view when the dev/build is called. We need to make sure all dependent objects have already been created, otherwise the dev/build on an empty database will just crash. SilverStripe doesn't come with a "nice" way to trigger this in a proper moment, so I plugged it in the requireDefaultRecords:

	function requireDefaultRecords() {
		if (DB::getConn()->getDatabaseServer()!='mssql') return;
		
		if (DB::getConn()->hasTable('ForumSearch_view')) {
			DB::query("DROP VIEW ForumSearch_view");
		}
		
		DB::query("
			CREATE VIEW \"dbo\".\"ForumSearch_view\" WITH SCHEMABINDING AS
			SELECT \"Post\".\"ID\" AS \"PostID\", \"Post\".\"ThreadID\", \"Post\".\"ForumID\",
				\"ForumThread\".\"Title\", \"Post\".\"Content\", \"Member\".\"FirstName\", \"Member\".\"Surname\"
			FROM \"dbo\".\"Post\"
			JOIN \"dbo\".\"ForumThread\" ON \"Post\".\"ThreadID\" = \"ForumThread\".\"ID\"
			JOIN \"dbo\".\"Member\" ON \"Post\".\"AuthorID\"=\"Member\".\"ID\"
			WHERE \"Post\".\"ThreadID\" IS NOT NULL AND \"Post\".\"ForumID\" IS NOT NULL
		");

		DB::alteration_message("ForumSearch_view created","created");

		DB::query("
			CREATE UNIQUE CLUSTERED INDEX \"IX_ForumSearch_view_PostID\" ON \"ForumSearch_view\" (\"PostID\")
		");
		DB::query("CREATE FULLTEXT INDEX ON \"ForumSearch_view\" (\"Title\",\"Content\",\"FirstName\",\"Surname\") KEY INDEX \"IX_ForumSearch_view_PostID\" WITH CHANGE_TRACKING AUTO");
		DB::alteration_message("ForumSearch_view index created","created");
	}

This can be added to any SiteTree class on the site, but I think it's reasonable to put it on a forum decorator just to keep things clean.

I'm dropping and recreating the view every time dev/build is called, so I don't have to check for the schema updates. View has no persistent data, so we can do this:

	if (DB::getConn()->hasTable('ForumSearch_view')) {
		DB::query("DROP VIEW ForumSearch_view");
	}

//add a description why we use \"dbo\" and SCHEMABINDING

The CREATE VIEW query uses the JOIN to merge the data together, and then I CREATE INDEX on this newly created view, picking up only the columns I need. Note that the ID is necessary on the JOIN query as I need to be able to not only find keywords, but also figure out to which posts they belong to:

	SELECT \"Post\".\"ID\" AS \"PostID\", \"Post\".\"ThreadID\", \"Post\".\"ForumID\",
		\"ForumThread\".\"Title\", \"Post\".\"Content\", \"Member\".\"FirstName\", \"Member\".\"Surname\"

All the remaining fields are not neccessary, we will pick them up later while executing the search itself.

## Database driver adjustments

Unfortunately this setup has a drawback: before each test the TestRunner calls SapphireTest::empty_temp_db. This attempts to TRUNCATE tables, but forum objects still have the view attached to them. This will result in MSSQL throwing an error. We need a way to clean these tables up before the TestRunner has a chance to call setUpOnce.

The best way I found to do it is to actually extend the database driver and clean the view each time the schema is being rebuilt. This ensures the code is called both when executing dev/build and when running tests:

	class CgpsMSSQLDatabase extends MSSQLDatabase {
		function beginSchemaUpdate() {
			$retval = parent::beginSchemaUpdate();

			if (DB::getConn()->hasTable('ForumSearch_view')) {
				DB::query("DROP VIEW ForumSearch_view");
			}

			return $retval;
		}
	}

## Database parameters

This is not enough to get it all running, as MSSQL has certain requirements for connection strings when dealing with views. That's also where driver extension comes in handy: 

	class MyMSSQLDatabase extends MSSQLDatabase {
		public function __construct($parameters) {
			parent::__construct($parameters);

			if($this->dbConn) {
				$this->database = $parameters['database'];
				$this->selectDatabase($this->database);
				$this->query('SET ANSI_NULLS ON'); 
				$this->query('SET CONCAT_NULL_YIELDS_NULL ON'); 
				$this->query('SET ANSI_WARNINGS ON'); 
				$this->query('SET ANSI_PADDING ON'); 
			}
		}

		function beginSchemaUpdate() {
			...
		}
	}

Finally we need to start using the new driver. Additional line in the _config.php will accomplish that:

	require_once("conf/ConfigureFromEnv.php");
	$databaseConfig['type'] = 'MyMSSQLDatabase';

## Actual search

Now that we have a view with full-text indexing on it, we can put it to use. This will only work on MSSQL database: 

	public function getResults($forumHolderID, $query, $order, $offset = 0, $limit = 10) {
		if (DB::getConn()->getDatabaseServer()!='mssql') return new DataObjectSet;
		if (!$query) return new DataObjectSet;

		$q = new SQLQuery;
		$q->from("
			\"Post\"
			JOIN FREETEXTTABLE(\"ForumSearch_view\", (*), '$query') AS \"ft\" ON \"ft\".\"KEY\"=\"Post\".\"ID\"
		");

		// Get the total
		$q->select('COUNT(*)');
		$total = $q->execute()->value();
		if (!$total) return new DataObjectSet;

		$q->select('*');
		if ($order=='date') {
			$q->orderby("\"Post\".\"LastEdited\" DESC");
		}
		else {
			$q->orderby("\"ft\".\"RANK\" DESC");
		}
		$q->limit = "$offset,$limit";
		$results = $q->execute();

		$postsSet = singleton('Post')->buildDataObjectSet($results);
		
		if($postsSet) {
			$postsSet->setPageLimits($offset, $limit, $total);
		}
		
		return $postsSet ? $postsSet : new DataObjectSet();
	}
