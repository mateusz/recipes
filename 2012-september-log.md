### Finding all DataObject's relationships ###

```php
$allRelations = array_merge(
	($relations = Config::inst()->get($this->class, 'has_one')) ? $relations : array(),
	($relations = Config::inst()->get($this->class, 'has_many')) ? $relations : array(),
	($relations = Config::inst()->get($this->class, 'many_many')) ? $relations : array(),
	($relations = Config::inst()->get($this->class, 'belongs_many_many')) ? $relations : array(),
	($relations = Config::inst()->get($this->class, 'belongs_to')) ? $relations : array()
);
```