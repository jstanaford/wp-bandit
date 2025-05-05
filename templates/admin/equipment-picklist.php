<?php 
/**
 * Partial Template File for controlling the "Picklist" settings in the admin area. 
 * 
 * Pulls from the Bandit API to populate the dropdowns. Based on /includes/controllers/class-api.php 
 */
use WP_Bandit\Controllers\API;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

$api = API::instance();
$refresh_api = isset($_GET['refresh_api']) ? true : false;

if ( empty( get_option('bandit_cached_listing') ) || $refresh_api ) {
    $all_products_by_cat = $api->fetch_all(true);
    update_option('bandit_cached_listing', $all_products_by_cat);
} else {
    $all_products_by_cat = get_option('bandit_cached_listing');
}
if ( empty( get_option ('bandit_cached_images') ) || $refresh_api ) {
    $images = [];
} else {
    $images = get_option('bandit_cached_images');
}

$currently_selected_products = !empty(get_option('bandit_selected_products')) ? get_option('bandit_selected_products') : [];


?>
<div class="wrap">
    <p>Select the Equipment to save to the site by checking off the box to the right of the item desired. Click Save Selection when completed, and then shift to the Importer Tab to run the import.</p>
    <p> <i>Please note - the currently listing is based on a "cached" view of the Bandit API. If suspect there are recently added new products to Bandit, you may need to refresh the cache by clicking the "Refresh API" button below.</i></p>
<form id="wp-bandit-picklist" method="post" action="options.php">
    <div style="display:flex;">
        <?php submit_button('Save Selection'); ?> <a  style="margin-left: 25px;max-height: 30px;margin-top: 30px;" href="<?php echo admin_url('admin.php?page=wp-bandit-equipment&tab=equipment-picklist&refresh_api'); ?>" class="button button-primary">Refresh API</a>
    </div>
    <?php settings_fields('wp_bandit_settings'); ?>
    <table class="form-table" >
        <tbody class="js-accordion">
            <?php 
                $prod_index = 1; 
            ?>
            <?php foreach($all_products_by_cat as $cat => $products): ?>
                <tr style="border:2px solid #23282e" id="product-type-section-<?php echo str_replace(' ', '-', strtolower( $cat ) ); ?>"  >

                        <th colspan="4" style="background-color:#23282e;padding: 10px 30px" >
                            <h3 style="color:#fff" ><?php echo $cat; ?></h3>
                        </th>


                        <?php foreach($products as $product): ?>
                                <tr style="border:2px solid #ddd;" >
                                    <th style="background-color:#FFC20E;padding: 10px 30px" colspan="2"><?php echo $product['title']['rendered'] . ' - <a target="_blank" href="https://banditchippers.com/wp-json/wp/v2/project/' . $product['id'] . '">API link</a>'; ?> </th>
                                    <th style="background-color:#ddd;padding: 10px 30px">Save Item?</th>
                                </tr>
                                <tr style="border:2px solid #ddd" class="bandit_updater__config_row">
                                    <td valign="top">
                                        <?php 
                                        $first_img = $product['acf']['product_image_carousel_1'];
                                        if (isset($images[$first_img])) {
                                            $image_url = $images[$first_img];
                                        } else {
                                            $image = $api->get_media($first_img);
                                            $image_url = $image['source_url'] ?? ''; 
                                        }
                                        
                                        if( !empty($image_url)):
                                            $images[$first_img] = $image_url;
                                        ?>
                                        <img style="height:70px" src="<?php echo $image_url; ?>">
                                        <?php else: ?>
                                            <p>No Image Available. </p>
                                        <?php endif; ?>
                                    </td>
                                    <td valign="top">
                                        <p><a href="<?php echo $product['link']; ?>" target="_blank">View on Bandit</a>
                                        </p><p><i><?php echo $cat; ?></i></p>
                                    </td>
                                    <td valign="top">
                                        <input type="checkbox" name="bandit_selected_products[<?php echo $product['id']; ?>]" 
                                            value="<?php echo $product['id']; ?>" 
                                            id="checkbox<?php echo $prod_index; ?>" 
                                            <?php if  (in_array( $product['id'], $currently_selected_products ) ){ echo "checked"; } ?>
                                        > 
                                    </td>
                                </tr>
                        <?php $prod_index++; ?>
                        <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php submit_button('Save Selection'); ?>
</form>


</div>

<?php 

if ( empty( get_option ('bandit_cached_images') ) ) {
    update_option( 'bandit_cached_images', $images );
} 
