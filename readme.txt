=== UMW Directory Syndication ===
Contributors: cgrymala
Tags: rest api, syndication, directory, umw
Donate link: http://giving.umw.edu/
Requires at least: 4.0
Tested up to: 4.5
Stable tag: 0.1
License: GPL v2 or later

This plugin utilizes the WP REST API to allow syndication of employee directory information.

== Description ==
Before starting, it is recommended that you familiarize yourself with the [WP REST API v2 documentation](http://v2.wp-api.org/), as much of that will act as a foundation for using this plugin. In addition, you may want to track the documentation/development of the [SearchWP API plugin](https://github.com/CalderaWP/searchwp-api-route) for reference on using search methods that aren't explicitly included in this plugin.

This plugin implements a handful of custom REST API endpoints, as well as a new shortcode  allowing syndication of directory information.

Most of the features outlined in this documentation are features of this plugin specifically. However, there is also some documentation that outlines native features of the [WP REST API](http://v2.wp-api.org/) and there is limited documentation related to a [3rd-party plugin called SearchWP API](https://github.com/CalderaWP/searchwp-api-route) that allows you to search using the API.

Additionally, some of the more complex routes that are registered and set up through this plugin are dependent on another plugin called [Types Relationship API](https://github.com/UMWEDU/types-relationship-api).

= Retrieving Individual Employees =
There are three different ways you can retrieve individual employees.

**By Username**
If you have the employee's SAM Account Name (Banner/AD username), you can retrieve information about the employee by using a URL in the structure of `/wp-json/wp/v2/employee/username/`

_This is a route that is registered and set up through this plugin._

**By Post ID**
If you have the WordPress post ID for the employee's entry by using a URL in the structure of `/wp-json/wp/v2/employee/`

_This is a route that is registered and set up natively through the REST API_

**By Searching**
If you do not have the WordPress post ID or the user's SAM Account Name, you can attempt to search for the employee by providing whatever information you do have, and using a search with a URL in the structure of `/wp-json/swp_api/search?post_type=employee&s=`.

_This is a route implemented by a 3rd-party plugin called [SearchWP API](https://github.com/CalderaWP/searchwp-api-route). More documentation about this search functionality can be found there._

= Retrieving All Employees in a Department or Building =
**By Post ID**
If you have the WordPress post ID of the department or building, you can retrieve the list of employees by using a URL structure like `/wp-json/types/relationships/v1/department/employee/<id>` or `/wp-json/types/relationships/v1/building/employee/<id>`

**By Post Path**
If you do not have the WordPress post ID, but you do have the post slug (for instance, the slug for the Admissions department is "admissions"), you can use a URL structure like `/wp-json/types/relationships/v1/department/employee?slug=<slug>` or `/wp-json/types/relationships/v1/building/employee?slug=<slug>`.

The underlying function that retrieves the department/building is the native WordPress get_page_by_path() function, which, for hierarchical post types, requires the hierarchy to be in tact when searching. Therefore, if you are trying to retrieve information about a building/department that is nested under another building/department, you will need the full path of the page that follows `/directory/department/` or `/directory/building/`. For instance, the URL for Alumni Relations is `/directory/department/advancement/advancement/alumni-relations/`, so the "path" or "slug" you'll need is `advancement/advancement/alumni-relations`.

= Retrieving General Building or Department Information =

You can also use the native WP REST API functionality to retrieve general information about an individual building or department. This is not new functionality implemented by this plugin, but it is worth noting within this documentation, regardless.

**By Post ID**
If you have the WordPress post ID for the department or building you're searching for, you can use a URL structure like `/wp-json/wp/v2/department/<id>` or `/wp-json/wp/v2/building/<id>`.

**By Searching**
If you do not have the WordPress post ID, you can attempt to search for the department or building by using the functionality _implemented by the [SearchWP API plugin](https://github.com/CalderaWP/searchwp-api-route)_. To do so, you would use a URL structure like `/wp-json/swp_api/search?post_type=building&s=<search term>` or `/wp-json/swp_api/search?post_type=department&s=<search term>`.

Once you have retrieved a list of the departments/buildings that match your search criteria, it may be necessary to process that list of items in order to find the ID of the individual department/building you would like to use (for instance, if you are planning to use that information to then retrieve a list of employees related to that item).

== Installation ==
This plugin should work as a standard plugin, but is intended to act as a mu-plugin.

This plugin is dependent on the [Types Relationship API plugin](https://github.com/UMWEDU/types-relationship-api), so that plugin must be installed (either as a mu-plugin or as an active plugin) before you can activate this plugin.

To install as a regular plugin, upload the entire `umw-directory-syndication` folder into `wp-content/plugins`, then activate the plugin.

To install as a mu-plugin, upload the contents of the `umw-directory-syndication` folder to `wp-content/mu-plugins`.

== Changelog ==
= 0.1 =
* First release
