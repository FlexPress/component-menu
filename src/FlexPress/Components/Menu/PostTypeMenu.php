<?php

namespace FlexPress\Components\Menu;

class PostTypeMenu implements MenuInterface
{

    /**
     * @var array
     */
    protected $defaultArgs;

    public function __construct()
    {

        $this->defaultArgs = array(

            'starting_level' => 1,
            'post_type' => 'page',
            'post_id' => 0,
            'sudo_items' => array(),
            'force_current' => null,
            "recurse" => true

        );

    }

    /**
     *
     * Outputs the menu with the given args
     *
     * @param array $args
     * @throws \RuntimeException
     * @author Tim Perry
     */
    public function output(array $args = array())
    {

        if (get_the_ID()) {
            $this->defaultArgs['post_id'] = get_the_ID();
        }

        $args = array_merge($this->defaultArgs, $args);

        if (!empty($args['sudo_items'])) {
            $args['sudo_items'] = $this->getSudoObjects($args['sudo_items']);
        }

        // zero index
        $args['starting_level'] -= 1;
        $args['level'] = $args['starting_level'];

        $args['ancestors'] = $this->getAncestors($args);

        if ($this->validateArgs($args)) {
            $this->recurse($args);
        }

    }

    /**
     *
     * Recurses with the given args
     *
     * @author Tim Perry
     *
     */
    protected function recurse(array $args)
    {

        $pages = $this->getPagesWithSudoItems($args);

        if (empty($pages)) {
            return;
        }

        $nextParentID = $this->getNextParentID($args);
        $this->outputMenu($pages, $args, $nextParentID);

    }

    /**
     *
     * Loops through the given pages and delegates their output
     * to various methods(override as necessary)
     *
     * @param $pages
     * @param $args
     * @param $nextParentID
     * @author Tim Perry
     */
    protected function outputMenu($pages, $args, $nextParentID)
    {
        $this->outputOpeningUl($args['level']);

        foreach ($pages as $page) {

            $this->outputOpeningLi($page, $args['level']);

            $this->outputPageLink($page, $args['level'], $args);

            if ($args["recurse"]
                && ($page->ID == $nextParentID)
            ) {

                $nextLevelArgs = $args;
                $nextLevelArgs['level']++;
                $nextLevelArgs['parent_id'] = $page->ID;

                $this->recurse($nextLevelArgs);

            }

            $this->outputClosingLi($page, $args['level']);

        }

        $this->outputClosingUl($args['level']);

    }

    /**
     *
     * Outputs the opening ul for a given level
     *
     * @author Tim Perry
     *
     */
    protected function outputOpeningUl($level)
    {
        echo '<ul class="level-', $level + 1, '">';
    }

    /**
     *
     * Outputs the closing ul for a given level
     *
     * @param $level
     * @author Tim Perry
     *
     */
    protected function outputClosingUl($level)
    {
        echo "</ul>";
    }

    /**
     *
     * Outputs the opening li for a given level and page
     *
     * @param $page
     * @param $level
     * @author Tim Perry
     *
     */
    protected function outputOpeningLi($page, $level)
    {
        echo "<li>";
    }

    /**
     *
     * Outputs the closing ul for a given level and page
     *
     * @param $page
     * @param $level
     * @author Tim Perry
     *
     */
    protected function outputClosingLi($page, $level)
    {
        echo "</li>";
    }

    /**
     *
     * Outputs the page link for a given level, page and args
     *
     * @param $page
     * @param $level
     * @param $args
     * @author Tim Perry
     */
    protected function outputPageLink($page, $level, $args)
    {
        $classes = '';
        if (in_array($page->ID, $args['ancestors'])
            || $page->ID == $args['force_current']
        ) {
            $classes = ' class="is-current"';
        }

        $permalink = (property_exists($page, 'permalink')) ? $page->permalink : get_permalink($page->ID);

        echo '<a href="', $permalink, '"', $classes, '>', $page->post_title, '</a>';

    }

    /**
     *
     * Validates the arguments passed to the output menu function
     *
     * @param $args
     * @throws \RuntimeException
     * @return bool
     * @author Tim Perry
     */
    protected function validateArgs($args)
    {
        $totalAncestors = count($args['ancestors']);

        if ($args['starting_level'] >= $totalAncestors) {

            $message = __CLASS__ . ': Invalid starting level of ';
            $message .= $args['starting_level'] . ' exceed the total ' . $totalAncestors;

            throw new \RuntimeException($message);

        }

        return true;

    }

    /**
     *
     * For the array of given sudo items,
     * returns the object
     *
     * @param array $sudoItems
     * @return array
     * @author Tim Perry
     */
    protected function getSudoObjects(array $sudoItems)
    {

        $item_objects = array();

        if (!empty($sudoItems)) {

            foreach ($sudoItems as $parentID => $items) {

                $item_objects[$parentID] = array();

                foreach ($items as $item) {

                    $item_object = new \stdClass();
                    $item_object->ID = $item['id'];
                    $item_object->post_title = $item['title'];
                    $item_object->permalink = $item['link'];

                    $item_objects[$parentID][] = $item_object;

                }

            }

        }

        return $item_objects;

    }


    /**
     *
     * Gets the pages for the given args
     * and combines then with sudo items also given
     * in the args
     *
     * @param $args
     * @return array
     * @author Tim Perry
     */
    protected function getPagesWithSudoItems($args)
    {

        /**
         * if a parent id is set use that, if not grab a parent id from the ancestors, i.e. on top level grab which
         * one should be used as simply using 0 would mean we get the top level of the site, which is typically
         * not what we want, so by using the ancestors along with the level, which should be the starting level
         * then we can ensure that we get the correct parent id.
         **/
        $parentID = 0;

        if ((isset($args['parent_id']))) {
            $parentID = $args['parent_id'];
        } elseif (isset($args['ancestors'][$args['level']])) {
            $parentID = $args['ancestors'][$args['level']];
        }

        $getPostsArgs = array(

            'child_of' => $parentID,
            'post_parent' => $parentID,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'numberposts' => '-1',
            'post_type' => $args['post_type'],
            'post_status' => 'publish',
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => 'fp_page_type',
                    'value' => 'nonmenu',
                    'compare' => '!='
                ),
                array(
                    'key' => 'fp_page_type',
                    'value' => 'nonmenu',
                    'compare' => 'NOT EXISTS'
                )

            )

        );

        $pages = get_posts($getPostsArgs);

        if ($args['sudo_items'] && array_key_exists($parentID, $args['sudo_items'])) {
            $pages = array_merge($pages, $args['sudo_items'][$parentID]);
        }

        return $pages;

    }

    /**
     *
     * Returns the next parent ID for the given args
     *
     * @param $args
     * @return bool
     * @author Tim Perry
     *
     */
    protected function getNextParentID($args)
    {

        if (array_key_exists($args['level'] + 1, $args['ancestors'])) {
            return $args['ancestors'][$args['level'] + 1];
        }

        return false;

    }

    /**
     *
     * Builds the array of ancestors
     *
     * @param $args
     * @return array
     * @author Tim Perry
     */
    protected function getAncestors($args)
    {

        if (!isset($args['post_id'])
            || $args['post_id'] == 0
        ) {
            return array();
        }

        $ancestors = get_post_ancestors($args['post_id']);
        $ancestors = array_reverse($ancestors);
        $ancestors[] = $args['post_id'];

        return $ancestors;

    }
}
