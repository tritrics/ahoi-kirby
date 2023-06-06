# Kirby Rest Api Plugin

Get data out of [Kirby CMS](https://getkirby.com) with a Rest Api to use Kirby as a data backend for any other webservice . Early release - a detailed documentation is comming later.

The data is returned as JSON-Object.

Works together with a [Kirby Rest Api Vue Plugin](https://github.com/tritrics/kirby-rest-api-vue-plugin/tree/develop) to use Kirby's data in Vue applications.

## Installation

Download and copy  to `/site/plugins/tritrics-rest-api`.

Or install it with composer: 

```sh
$ cd /your/kirby/folder
$ composer require tritrics/kirby-rest-api
```

## Settings

You need to enable the Plugin in ```/site/config/config.php```:

```
'tritrics.restapi.enabled' => true

# or enable/disable each service
'tritrics.restapi.enabled.languages' => true,
'tritrics.restapi.enabled.node' => true,
'tritrics.restapi.enabled.children' => true,

# also you can optional set the slug under which the api is found (default is /rest-api)
'tritrics.restapi.slug' = '/your-individual-slug',
```

Also each field, that should be published, has to be configured in the blueprint with ```api: true```

```
fields:
  myfield:
    type: text
    label: My Field
    api: true <--
		...
```

## Methods

As shown above in the settings section, there exist 3 methods to receive different information. Replace ```/rest-api``` in the following examples with your personal slug, if you have set one in ```config.php```.

### Languages

Get information about the languages if Kirby is in multilang-mode. (Method is not present in singlelang-mode.) Multilang-mode can be switched on in ```site/config/config.php```, see [documentation](https://getkirby.com/docs/guide/languages/introduction).

```http://my-domain.com/rest-api/languages```

### Node

Get information and data from the a page or the site (as defined in ```blueprints/site.yml```).

```http://my-domain.com/rest-api/node[/langcode][/path/of/slugs]```

- **langcode** is optional and only needed in multilang sites
- **/path/of/slugs** is the url to the page, leave blank to get the site

To get the fields-data add:

```?fields=all``` or

```?fields[]=myfield&fields[]=otherfield```

The GET-query can also be replaced with POST-data:

```{ fields: [ myfield, otherfield ]}```

### Children

Instead of returning the field-data, the **listed** child nodes from Kirby's pages section are returned (if existing in blueprint). Leave path empty to return children of the site.

```http://my-domain.com/rest-api/children[/langcode][/path/of/slugs]```

Here the result can be configured by GET or POST-parameters:

```
?limit=10 (default) get only 10 childs
?page=1 (default) for pagination
?order=asc|desc (asc=ascending, default) the sort-order
?fields=all|array like in node
```

## Images

Resized images can be easily requested, which should by normally necessary when the Api is used for websites. The required data is present in the response-data of image fields:

```
{
  dir: [http://domain.com/the/path/to/image/dir],
  file: [the files basename, without extension],
  ext: [the extension],
  width: [the image width],
  height: [the image height],
  ...
}
```

With this information an image url like the following can be created:

```http://domain.com/dir/file[-(width)x(height)][-crop-(option)][-blur(integer)][-bw][-q(integer)].ext```

Explaination (everything similar to Kirby's thumb()-function):

- **with** and **height** are the request max. dimension for the resized image (If they don't have the same ratio like the orignal, use crop-option. Otherwise the width **or** the height of the returned image will not be like requested - acutallly it will only fit in the rectangle.)
- **crop**-option can be: 'top-left', 'top', 'top-right', 'left', 'center', 'right', 'bottom-left', 'bottom', 'bottom-right'
- **blur**-option is an integer > 0 for the blur factor
- **bw** is for converting the image to black/white
- **q** is the jpg quality <= 100

All options but the dimensions are optional.

(For Vue the  [Kirby Rest Api Vue Plugin](https://github.com/tritrics/kirby-rest-api-vue-plugin/tree/develop) does this with a handy interface, also the calculation of the dimensions with preserving the ratio.)

## License

MIT

## Author

Michael Adams, Denmark  
E-Mail: [ma@tritrics.dk](mailto:ma@tritrics.dk)  
