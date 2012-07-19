### 19 July 2012, Friday

The internet is restless today. I needed some context for search results from my databases.

```php
function results($data, $form, $request) {
	$results = $form->getResults();
	$query = $form->getSearchQuery();

	// Add context summaries based on the queries.
	foreach ($results as $result) {
		$contextualTitle = new Text();
		$contextualTitle->setValue($result->MenuTitle);
		$result->ContextualTitle = $contextualTitle->ContextSummary(300, $query);

		$result->ContextualContent = $result->obj('Content')->ContextSummary(300, $query);
	}

	// Render the result.
	$data = array(
		'Results' => $results,
		'Query' => $query,
		'Title' => _t('SearchForm.SearchResults', 'Search Results')
	);

	return $this->owner->customise($data)->renderWith(array('Page_results', 'Page'));
}
```

This injects contextual titles and content - and includes highlighting as `<span class="highlight">`.