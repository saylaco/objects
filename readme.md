```php
[
    'section:object' =>  new BelongsToObject(Section::class, 'sectionId')
]

[
    'block_data:json' => ['default' => function () {
        return new DotArray();
    }],
]

[
    'resolver:callback' => ['autoResolve' => true],
]

[
	'field_ref:uid' => ['rules' => 'required'],
	'entry_id:fk' => ['store' => false, 'writable' => false]
]


 * @property UrlGroup $urls

[
    'urls:serial'
]

public function resolveUrlsAlias()
{
    return new UrlGroup([
        'createBlock' => ['block.create', $this->alias],
        'storeBlock' => ['block.store', $this->alias],
        'show' => ['blockType.show', $this->alias],
    ]);
}
```