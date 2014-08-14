# FlexPress menu helpers component

## Install via Pimple
```
$pimple["menu"] = function() {
  return new PostTypeMenu();
};
```

## Usage
```
$defaults = array(

  'starting_level' => 1,
  'post_type' => 'page',
  'post_id' => $GLOBALS['post']->ID,
  'sudo_items' => array(),
  'force_current' => null,
  "recurse" => true

);

$pimple["menu"]->output($defaults);
```

## Options

#### starting_level
This is what level the meny should start displaying on, for example if you have Careers > Vacancies > Vacancy listings and you set the level as 1(default) then it would display Vacancies and skip the Careers level.

#### post_type
This option allows you to specify what post type you want to use, defaults to page

#### post_id
This option lets you change the post_id that is used to get the ancestors and is also used to set the current page in the menu.

#### sudo_items
This option allows you to create sudo menu items, an example config would look like this:

```
$args['sudo_items'] = array(
  
  123 => array(
    "id" => -1,
    "title" => "Some random link",
    "link" => "/some/random/link",
  )

);
```

This would add a page with the title Some random link, which links to /some/random/link under the item in the menu that has the id 123.

#### force_current
You can use this option to speficy what the current link is, this is a post_id

#### recurse
You can turn off recurse to make the menu not recurse and just output the current level.
