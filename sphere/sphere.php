<?php
/*
Plugin Name: Sphere caaatpro plugin
Description: 3D Sphere plugin
Author: caaatpro
Author URI: https://caaat.pro/
Version: 1.0
*/


global $custom_table_example_db_version;
$custom_table_example_db_version = '1.0';

function custom_table_example_install() {
    global $wpdb;
    global $custom_table_example_db_version;

    $table_name = $wpdb->prefix . 'sphere';

    $sql = "CREATE TABLE " . $table_name . " (
      id int(11) NOT NULL AUTO_INCREMENT,
      name tinytext NOT NULL,
      text tinytext NOT NULL,
      num int(11) NULL,
      PRIMARY KEY (id)
    );";

    // we do not execute sql directly
    // we are calling dbDelta which cant migrate database
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // save current database version for later use (on upgrade)
    add_option('custom_table_example_db_version', $custom_table_example_db_version);


    $installed_ver = get_option('custom_table_example_db_version');
    if ($installed_ver != $custom_table_example_db_version) {
        $sql = "CREATE TABLE " . $table_name . " (
          id int(11) NOT NULL AUTO_INCREMENT,
          name tinytext NOT NULL,
          text tinytext NOT NULL,
          num int(11) NULL,
          PRIMARY KEY (id)
        );";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // notice that we are updating option, rather than adding it
        update_option('custom_table_example_db_version', $custom_table_example_db_version);
    }
}

register_activation_hook(__FILE__, 'custom_table_example_install');

function custom_table_example_install_data() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'sphere'; // do not forget about tables prefix
}

register_activation_hook(__FILE__, 'custom_table_example_install_data');

function custom_table_example_update_db_check() {
    global $custom_table_example_db_version;
    if (get_site_option('custom_table_example_db_version') != $custom_table_example_db_version) {
        custom_table_example_install();
    }
}

add_action('plugins_loaded', 'custom_table_example_update_db_check');

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Custom_Table_Example_List_Table extends WP_List_Table {
    function __construct() {
        global $status, $page;

        parent::__construct(array(
            'singular' => 'sphere',
            'plural' => 'sphere',
        ));
    }

    function column_default($item, $column_name) {
        return $item[$column_name];
    }

    function column_num($item) {
        return '<em>' . $item['num'] . '</em>';
    }

    function column_name($item) {
        $actions = array(
            'edit' => sprintf('<a href="?page=sphere_form&id=%s">%s</a>', $item['id'], __('Edit', 'custom_table_example')),
            'delete' => sprintf('<a href="?page=%s&action=delete&id=%s">%s</a>', $_REQUEST['page'], $item['id'], __('Delete', 'custom_table_example')),
        );

        return sprintf('%s %s',
            $item['name'],
            $this->row_actions($actions)
        );
    }

    function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="id[]" value="%s" />',
            $item['id']
        );
    }

    function get_columns() {
        $columns = array(
            'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
            'name' => __('Name', 'custom_table_example'),
            'text' => __('Text', 'custom_table_example'),
            'num' => __('Num', 'custom_table_example'),
        );
        return $columns;
    }

    function get_sortable_columns() {
        $sortable_columns = array(
            'name' => array('name', true),
            'text' => array('text', false),
            'num' => array('num', false),
        );
        return $sortable_columns;
    }

    function get_bulk_actions() {
        $actions = array(
            'delete' => 'Delete'
        );
        return $actions;
    }

    function process_bulk_action() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sphere'; // do not forget about tables prefix

        if ('delete' === $this->current_action()) {
            $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();
            if (is_array($ids)) $ids = implode(',', $ids);

            if (!empty($ids)) {
                $wpdb->query("DELETE FROM $table_name WHERE id IN($ids)");
            }
        }
    }

    function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sphere'; // do not forget about tables prefix

        $per_page = 5; // constant, how much records will be shown per page

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        // here we configure table headers, defined in our methods
        $this->_column_headers = array($columns, $hidden, $sortable);

        // [OPTIONAL] process bulk action if any
        $this->process_bulk_action();

        // will be used in pagination settings
        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");

        // prepare query params, as usual current page, order by and order direction
        $paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged']) - 1) : 0;
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'name';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'asc';

        // [REQUIRED] define $items array
        // notice that last argument is ARRAY_A, so we will retrieve array
        $this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged), ARRAY_A);

        // [REQUIRED] configure pagination
        $this->set_pagination_args(array(
            'total_items' => $total_items, // total items defined above
            'per_page' => $per_page, // per page constant defined at top of method
            'total_pages' => ceil($total_items / $per_page) // calculate pages count
        ));
    }
}

function custom_table_example_admin_menu() {
    add_menu_page(__('Sphere', 'custom_table_example'), __('Sphere', 'custom_table_example'), 'activate_plugins', 'sphere', 'custom_table_example_sphere_page_handler');
    add_submenu_page('sphere', __('All text', 'custom_table_example'), __('Sphere', 'custom_table_example'), 'activate_plugins', 'sphere', 'custom_table_example_sphere_page_handler');
    // add new will be described in next part
    add_submenu_page('sphere', __('Add new', 'custom_table_example'), __('Add new', 'custom_table_example'), 'activate_plugins', 'sphere_form', 'custom_table_example_sphere_form_page_handler');
}

add_action('admin_menu', 'custom_table_example_admin_menu');

function custom_table_example_sphere_page_handler() {
    global $wpdb;

    $table = new Custom_Table_Example_List_Table();
    $table->prepare_items();

    $message = '';
    if ('delete' === $table->current_action()) {
        $message = '<div class="updated below-h2" id="message"><p>' . sprintf(__('Items deleted: %d', 'custom_table_example'), count($_REQUEST['id'])) . '</p></div>';
    }
    ?>
<div class="wrap">

    <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
    <h2><?php _e('sphere', 'custom_table_example')?> <a class="add-new-h2"
                                 href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=sphere_form');?>"><?php _e('Add new', 'custom_table_example')?></a>
    </h2>
    <?php echo $message; ?>

    <form id="sphere-table" method="GET">
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>
        <?php $table->display() ?>
    </form>

</div>
<?php
}


function custom_table_example_sphere_form_page_handler() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'sphere'; // do not forget about tables prefix

    $message = '';
    $notice = '';

    // this is default $item which will be used for new records
    $default = array(
        'id' => 0,
        'name' => '',
        'text' => '',
        'num' => null,
    );

      if (isset($_REQUEST['nonce']) && wp_verify_nonce($_REQUEST['nonce'], basename(__FILE__))) {
          $item = shortcode_atts($default, $_REQUEST);
          $item_valid = custom_table_example_validate_sphere($item);
          if ($item_valid === true) {
              if ($item['id'] == 0) {
                  $result = $wpdb->insert($table_name, $item);
                  $item['id'] = $wpdb->insert_id;
                  if ($result) {
                      $message = __('Item was successfully saved', 'custom_table_example');
                  } else {
                      $notice = __('There was an error while saving item', 'custom_table_example');
                  }
              } else {
                  $result = $wpdb->update($table_name, $item, array('id' => $item['id']));
                  if ($result) {
                      $message = __('Item was successfully updated', 'custom_table_example');
                  } else {
                      $notice = __('There was an error while updating item', 'custom_table_example');
                  }
              }
          } else {
              $notice = $item_valid;
          }
      } else {
          $item = $default;
          if (isset($_REQUEST['id'])) {
              $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $_REQUEST['id']), ARRAY_A);
              if (!$item) {
                  $item = $default;
                  $notice = __('Item not found', 'custom_table_example');
              }
          }
      }

    add_meta_box('sphere_form_meta_box', 'sphere data', 'custom_table_example_sphere_form_meta_box_handler', 'sphere', 'normal', 'default');

    ?>
<div class="wrap">
    <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
    <h2><?php _e('sphere', 'custom_table_example')?> <a class="add-new-h2"
                                href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=sphere');?>"><?php _e('back to list', 'custom_table_example')?></a>
    </h2>

    <?php if (!empty($notice)): ?>
    <div id="notice" class="error"><p><?php echo $notice ?></p></div>
    <?php endif;?>
    <?php if (!empty($message)): ?>
    <div id="message" class="updated"><p><?php echo $message ?></p></div>
    <?php endif;?>

    <form id="form" method="POST">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__))?>"/>
        <input type="hidden" name="id" value="<?php echo $item['id'] ?>"/>

        <div class="metabox-holder" id="poststuff">
            <div id="post-body">
                <div id="post-body-content">
                    <?php do_meta_boxes('sphere', 'normal', $item); ?>
                    <input type="submit" value="<?php _e('Save', 'custom_table_example')?>" id="submit" class="button-primary" name="submit">
                </div>
            </div>
        </div>
    </form>
</div>
<?php
}

function custom_table_example_sphere_form_meta_box_handler($item) {
    ?>

<table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
    <tbody>
    <tr class="form-field">
        <th valign="top" scope="row">
            <label for="name"><?php _e('Name', 'custom_table_example')?></label>
        </th>
        <td>
            <input id="name" name="name" type="text" style="width: 95%" value="<?php echo esc_attr($item['name'])?>"
                   size="50" class="code" placeholder="<?php _e('Your name', 'custom_table_example')?>" required>
        </td>
    </tr>
    <tr class="form-field">
        <th valign="top" scope="row">
            <label for="text"><?php _e('Text', 'custom_table_example')?></label>
        </th>
        <td>
            <input id="text" name="text" type="text" style="width: 95%" value="<?php echo esc_attr($item['text'])?>"
                   size="50" class="code" placeholder="<?php _e('Your Text', 'custom_table_example')?>" required>
        </td>
    </tr>
    <tr class="form-field">
        <th valign="top" scope="row">
            <label for="num"><?php _e('Num', 'custom_table_example')?></label>
        </th>
        <td>
            <input id="num" name="num" type="number" style="width: 95%" value="<?php echo esc_attr($item['num'])?>"
                   size="50" class="code" placeholder="<?php _e('Your num', 'custom_table_example')?>" required>
        </td>
    </tr>
    </tbody>
</table>
<?php
}

function custom_table_example_validate_sphere($item)
{
    $messages = array();

    if (empty($item['name'])) $messages[] = __('Name is required', 'custom_table_example');
    if (empty($item['text'])) $messages[] = __('Text is in wrong format', 'custom_table_example');
    if (!ctype_digit($item['num'])) $messages[] = __('Num in wrong format', 'custom_table_example');

    if (empty($messages)) return true;
    return implode('<br />', $messages);
}

function custom_table_example_scripts_styles() {
  wp_register_script( 'custom-script', plugins_url( '/js/script.js', __FILE__ ));
  wp_register_style( 'custom-style', plugins_url( '/css/style.css', __FILE__ ));

  wp_enqueue_style( 'custom-style' );
  wp_enqueue_script( 'custom-script' );
}

function sphere_shortcode($atts){
  global $wpdb;
  $atts = shortcode_atts(array('count'=>'-1'), $atts);
  $limit = 1000;
  if ($atts['count'] != '-1') {
    $limit = $atts['count'];
  }
  $table_name = $wpdb->prefix . 'sphere'; // do not forget about tables prefix

  $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name LIMIT %d", $limit), ARRAY_A);
?>
  <div id="sphere" class="sphere">
    <?php
      echo "<script>taga = [";
      foreach ($items as $item) {
        echo "{name: '".$item['name']."', text: '".$item['text']."', num: ".$item['num']."}, ";
      }
      echo "];</script>";
    ?>

  </div>
<?php

}
add_action( 'wp_enqueue_scripts', 'custom_table_example_scripts_styles' );
add_shortcode('sphere', 'sphere_shortcode');
