# Cheatsheets

## Git

Hard remove previous commit:

	git reset --hard HEAD~1

Track remote branch:

	git checkout -b branch origin/branch

If you copy things around via CLI, run this to update:

	git-update-index --refresh

Fetch master remote branch into incoming to inspect the changes:

	git fetch REMOTEREPO master:incoming

Show changes between branches

	git whatchanged

Add local tag here:

	git tag my-tag

Add signed tag here:

	git tag -s my-tag

Checkout the branch (and create):

	git checkout -b mybranch

Merge one commit:

	git cherry-pick COMMIT

Show origin details:

	git remote show origin

Poor man's GitX, add to ~/.gitconfig:

	[Alias]
		tree = log --oneline --decorate --graph

List repository configuration options:

	git config --list

List detailed commits:
	
	git log --stat

Interactive rebase:

	git rebase -i COMMIT

Pull changes into private branch:
	
	git rebase master

Review the commit:
	
	git diff ORIG_HEAD

Review staged changes (after add, before commit):
	
	git diff --cached

Split a commit:

	git rebase -i COMMIT && git reset HEAD

Another way of creating a tracking branch:
	
	git branch --track BRANCH origin/BRANCH

Grab part of svn repo:

	git svn clone -s -r 119251:HEAD svn://svn.silverstripe.com/silverstripe/projects/cgps/ .

Format a specific patch, with base dir change and commit range:

	git format-patch --relative=DIR --stdout COMMIT..COMMIT > FILE

Apply patch:

	git am FILE

Clean up the mess in untracked files (attention: recursive and deletes directories):

	git clean -d -f path

