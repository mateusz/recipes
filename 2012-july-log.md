### Highlighting for search results

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