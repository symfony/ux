# QuillJs Bundle for Symfony using Symfony UX

Symfony UX Bundle implementing the Quill JS Wysiwyg https://quilljs.com/

If you need a easy to use WYSIWYG (with no complex configuration) into a symfony project this is what you need.

* [Installation](#installation)


* [Basic Usage](#basic-usage)
* [Display Result](#display-result)


* [Customize quillJS with options and extra_options](#customize-options)
* [Handle images uploads](#image-upload-handling)


* [EasyAdmin Integration](#easyadmin-integration)
* [EasyAdmin Usage](#usage)

## Installation
### Step 1 Require bundle
```sh
  composer require symfony/ux-quill
```
If you are using the AssetMapper Component you're done !

### step 2 next run (If you are using webpack encore, not needed with AssetMapper)
``` sh
    yarn install --force
    yarn watch
```
OR
``` sh
    npm install --force
    npm run watch
``` 
It's done, you can use the QuillType to build a QuillJs WYSIWYG

You can add as many WYSIWYG fields inside same page like any normal fields.

# Basic Usage
In a form, use QuillType. It works like a classic Type except it has more options : e.g:
```php
    use Symfony\UX\QuillJs\Form\QuillType;
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // ...
            ->add('myField', QuillType::class)
        ;
    }
```

# Display result
in a twig template simply :
```
    <div>{{ myField|raw }}</div>
```
you can of course sanitize HTML if you need to for security reason, but don't forget to configure it
to your needs as many html balise and style elements will be removed by default.
Same goes in your Form configuration
```
    'sanitize_html' => false,
    'sanitizer' => 'my_awesome_sanitizer_config
```


For the most basic this is only what you have to do.
# Customize Options
```php
    use Symfony\UX\QuillJs\Form\QuillType;
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // ...
            ->add('myField', QuillType::class, [
                'quill_extra_options' => [
                    'height' => '780px',
                    'theme' => 'snow',
                    'placeholder' => 'Hello Quill WYSIWYG',
                ],
                'quill_options' => [
                // this is where you customize the WYSIWYG by creating one or many Groups
                // you can also build your groups using a classic array but many classes are covering every Quill available Fields (see below for detailed list)
                    QuillGroup::build(
                        new BoldInlineField(),
                        new ItalicInlineField(),
                        // and many more
                    ),
                    QuillGroup::build(
                        new HeaderField(HeaderField::HEADER_OPTION_1),
                        new HeaderField(HeaderField::HEADER_OPTION_2),
                        // and many more
                    ),
                    // Or add all available fields at once
                    QuillGroup::buildWithAllFields()
                ]
            ])
        ;
    }
```


## quill_options :
This is where you will choose what elements you want to display in your WYSIWYG.
You can build an array like you would do following the QuillJs official documentation
Or use a more convenient with Autocomplete using the many Fields Object in this bundle.
```
      QuillGroup::build(
          new HeaderField(HeaderField::HEADER_OPTION_1),
          new HeaderField(HeaderField::HEADER_OPTION_2),
      )
```
This example will display a h1 and h2 header options side by side
```
      QuillGroup::build(
          new HeaderField(HeaderField::HEADER_OPTION_1),
          new HeaderField(HeaderField::HEADER_OPTION_2),
      )
      QuillGroup::build(
          new BoldInlineField(),
          new ItalicInlineField(),
      )
```
This example will display a h1 and h2 header options side by side and another Group containing a Bold and an Italic fields

You can add as many Groups as you like or just One if you don't need the WYSIWYG options to have spaces between them.

### Fields
- Below is the list of available fields from QuillJS (https://v2.quilljs.com/docs/formats)

|        Field         |                                     Description                                      |   Available options    (options are available as class constants in each Field Class)    | Default option | QuillJS field name |
|:--------------------:|:------------------------------------------------------------------------------------:|:----------------------------------------------------------------------------------------:|:--------------:|:------------------:|
|      BoldField       |                                  mark text as bold                                   |                                            -                                             |                |        bold        |
|      ColorField      |                               Change color of the font                               |           array of colors (default is empty array to get quillJS default value           |                |       color        |
| BackGroundColorField |                     change background color of the selected text                     |           array of colors (default is empty array to get quillJS default value           |                |     background     |
|      AlignField      |                                Choose text alignment                                 |                           false (left), center, right, justify                           |      all       |       align        |
|    DirectionField    |                                Choose text direction                                 | rtl (right to left, this is the only option available this widget is more like a toggle) |      rtl       |     direction      |
|      FontField       |                                    Choose a font                                     |                             ''(sans serif) ,serif, monospace                             |      all       |        font        |
|   HeaderGroupField   |                           Display a list of header levels                            |                    1, 2, 3, 4, 5, 6, false (will only display normal)                    |      all       |       header       |
|     HeaderField      |                              Add a H1 or H2 widget only                              |                                           1, 2                                           |       1        |       header       |
|     IndentField      |                                 Add or Remove indent                                 |                                          +1, -1                                          |       +1       |       indent       |
|      ListField       |                                      Add a list                                      |                                  ordered, bullet, check                                  |    ordered     |        list        |
|     ScriptField      |                                                                                      |                                        sub, super                                        |      sub       |       script       |
|      SizeField       |                                   Change text size                                   |                            small, false (normal), large, huge                            |      all       |        size        |
|   BlockQuoteField    |                                     Quote a text                                     |                                            -                                             |                |     blockquote     |
|      CleanField      |                                  Clean text styling                                  |                                            -                                             |                |       clean        |
|    CodeBlockField    |                                   Add a code-block                                   |                                            -                                             |                |     code-block     |
|      CodeField       |                                    Add some code                                     |                                            -                                             |                |        code        |
|     FormulaField     |                   add a formula (with [Katex](https://katex.org/))                   |                                            -                                             |                |      formula       |
|      ImageField      | Add an image (see [quill_extra_options](#image-upload-handling) for uploads options) |                                            -                                             |                |       image        |
|     ItalicField      |                                 mark text as italic                                  |                                            -                                             |                |       italic       |
|      LinkField       |                                 Add a link to a text                                 |                                            -                                             |                |        link        |
|     StrikeField      |                                mark a text as striked                                |                                            -                                             |                |       strike       |
|    UnderlineField    |                               mark text as underlined                                |                                            -                                             |                |     underline      |
|      VideoField      |                                  add an embed video                                  |                                            -                                             |                |       video        |


- Below is a list of fields not available in QuillJS but taken from community

|   Field    | Description  | Available options  (options are available as class constants in each Field Class)  | Default option |
|:----------:|:------------:|:----------------------------------------------------------------------------------:|:--------------:|
| EmojiField | Add an emoji |                                         -                                          |                |



## quill_extra_options:

- **debug**: type:string, values: 'error', 'warn', 'log', 'info'  (you can use DebugOption class to build it)
- **height**: type string, examples: 200px, 200em, default: '200px'
- **theme**: type: string, values: 'snow', 'bubble', default: 'snow' (you can use ThemeOption class to build it)
- **placeholder**: type: string
- **upload_handler**: type: array (explained below)

### Image upload Handling
in ***ImageInlineField*** : QuillJS transform images in base64 encoded file by default to save your files.
However, you can specify a custom endpoint to handle image uploading and pass in response the entire public URL to display the image.
- currently handling :
- data sending in base64 inside a json
- OR
- in a multipart/form-data
```
    'quill_extra_options' => [
        ///
        'upload_handler' => [
            'type' => 'json',
            // or 'type' => 'form',
            'path' => '/my-custom-endpoint/upload',
        ]
    ],
```
- your endpoint must return the complete URL of the file example :
```
  https://upload.wikimedia.org/wikipedia/commons/thumb/6/6a/JavaScript-logo.png/480px-JavaScript-logo.png
```
- in json mode data will look like this by calling $request->getContent() and ```application/json``` in content-type headers
```
    "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAlgAAAJYCAQAAAAUb1BXAAAABGdBTUEAALGPC/xhBQAAACyygyyioiBqFCUIKC64x..."
```
- in form mode you will find a ```multipart/form-data``` in content-type headers and file will be present in $request->files named ```file```
- then you can handle it like you would do with a FileType



# Easyadmin Integration
- First create a quill-admin.js inside assets directory
```
    // start the Stimulus application
    import './bootstrap';
```
## When using AssetMapper
create a new entry in importmap.php
(the key must be quill-admin as it is the name used in the built-in QuillAdminField)
```
    'quill-admin' => [
        'path' => './assets/quill-admin.js',
        'entrypoint' => true,
    ],
```
and i should be done. but read below

WARNING => at the moment there seems to have an issue with easyadmin with the ->addAssetMapperEntries() function 
as I can not get it work as it should be.
a quick fix is to add in your crudControllers
```
    public function configureAssets(Assets $assets): Assets
    {
        $assets->addAssetMapperEntry('quill-admin');
        return parent::configureAssets($assets); // TODO: Change the autogenerated stub
    }
```

OR

in your dashboard
```
    public function configureAssets(): Assets
    {
        $assets = Assets::new();
        $assets->addAssetMapperEntry('quill-admin');

        return $assets;
    }
```

## When using webpack
- Next create in webpack.config a new entry
(the entry name must be quill-admin as it is the name used in the built-in QuillAdminField)
```
    .addEntry('quill-admin', './assets/quill-admin.js')
```
don't forget to recompile assets (yarn build/watch or npm equivalent).

## EasyAdmin
Then you can use the QuillAdminField like this :
```
    QuillAdminField::new('quill')
```

Or add custom options like you would do with the normal type
```
    QuillAdminField::new('quill')
        ->setFormTypeOptions([
            'quill_options' =>
                QuillGroup::build(
                    new BoldInlineField(),
                    new ItalicInlineField(),
                    new HeaderField(HeaderField::HEADER_OPTION_1),
                    new HeaderField(HeaderField::HEADER_OPTION_2),
                )
        ])
```
