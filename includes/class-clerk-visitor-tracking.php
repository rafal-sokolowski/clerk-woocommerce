<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Clerk_Visitor_Tracking {
    /**
     * Clerk_Visitor_Tracking constructor.
     */
    protected $logger;
    public function __construct() {
        $this->initHooks();
        require_once(__DIR__ . '/class-clerk-logger.php');
        $this->logger = new ClerkLogger();
    }

    /**
     * Init hooks
     */
    private function initHooks() {
        add_action( 'wp_footer', [ $this, 'add_tracking' ] );
        add_action( 'wp_ajax_nopriv_get_cart', [ $this, 'get_cart'] );
        add_action( 'wp_ajax_get_cart', [ $this, 'get_cart'] );
        add_action( 'init', [ $this, 'clerk_add_custom_shortcodes'] );

        $options = get_option('clerk_options');
        if (isset($options['collect_emails']) ) {
            add_action( 'woocommerce_review_order_before_submit', [$this, 'clerk_woocommerce_review_order_before_submit'], 99);
        }
    }

    /**
     * Include tracking
     */
    public function clerk_add_custom_shortcodes() {
        add_shortcode( 'clerk_product_id', [$this, 'clerk_shortcode_get_product_id']);
        add_shortcode( 'clerk_category_id', [$this, 'clerk_shortcode_get_category_id']);
        add_shortcode( 'clerk_cart_ids', [$this, 'clerk_shortcode_get_cart_ids']);
    }

    public function clerk_shortcode_get_product_id() {
        try {
            if ( ! is_admin() ) {
                $id = get_the_ID();
            } else {
                $id = null;
            }
            return $id;
        } catch (Exception $e) {
            $this->logger->error('ERROR clerk_shortcode_get_product_id', ['error' => $e->getMessage()]);
        }
    }

    public function clerk_shortcode_get_category_id() {
        try {
            if ( ! is_admin() ) {
                $category = get_queried_object();
                $id = $category->term_id;
            } else {
                $id = null;
            }
            return $id;
        } catch (Exception $e) {
            $this->logger->error('ERROR clerk_shortcode_get_category_id', ['error' => $e->getMessage()]);
        }
    }

    public function clerk_shortcode_get_cart_ids() {
        try {
            if ( ! is_admin() ) {
                $cart_ids = array();
                $items = WC()->cart->get_cart(); 
                foreach( $items as $cart_item ){
                    array_push($cart_ids, $cart_item['product_id']);
                }
                $cart_ids = json_encode($cart_ids);
            } else {
                $cart_ids = null;
            }
            return $cart_ids;
        } catch (Exception $e) {
            $this->logger->error('ERROR clerk_shortcode_get_cart_ids', ['error' => $e->getMessage()]);
        }
    }


    /**
     * Include tracking
     */
    public function add_tracking() {

        try {

            $options = get_option('clerk_options');

            //Default to true
            if (!isset($options['collect_emails'])) {
                $options['collect_emails'] = true;
            }

            if (isset($options['lang'])) {

                if ($options['lang'] == 'auto') {
                    $LangsAuto = [
                        'da_DK' => 'Danish',
                        'nl_NL' => 'Dutch',
                        'en_US' => 'English',
                        'en_GB' => 'English',
                        'fi' => 'Finnish',
                        'fr_FR' => 'French',
                        'fr_BE' => 'French',
                        'de_DE' => 'German',
                        'hu_HU' => 'Hungarian',
                        'it_IT' => 'Italian',
                        'nn_NO' => 'Norwegian',
                        'nb_NO' => 'Norwegian',
                        'pt_PT' => 'Portuguese',
                        'pt_BR' => 'Portuguese',
                        'ro_RO' => 'Romanian',
                        'ru_RU' => 'Russian',
                        'ru_UA' => 'Russian',
                        'es_ES' => 'Spanish',
                        'sv_SE' => 'Swedish',
                        'tr_TR' => 'Turkish'
                    ];

                    $Lang = strtolower($LangsAuto[get_locale()]);

                } else {

                    $Lang = $options['lang'];

                }

            }

            ?>
            <!-- Start of Clerk.io E-commerce Personalisation tool - www.clerk.io -->
            <script>
                (function(w,d){
                    var e=d.createElement('script');e.type='text/javascript';e.async=true;
                    e.src=(d.location.protocol=='https:'?'https':'http')+'://cdn.clerk.io/clerk.js';
                    var s=d.getElementsByTagName('script')[0];s.parentNode.insertBefore(e,s);
                    w.__clerk_q=w.__clerk_q||[];w.Clerk=w.Clerk||function(){w.__clerk_q.push(arguments)};
                })(window,document);

                Clerk('config', {
                    key: '<?php echo $options['public_key']; ?>',
                    collect_email: <?php echo $options['collect_emails'] ? 'true' : 'false'; ?>,
                    language: '<?php echo $Lang; ?>'
                });
            </script>
            <!-- End of Clerk.io E-commerce Personalisation tool - www.clerk.io -->
            <?php

            if(isset( $options['clerk_additional_scripts_enabled'])){
                if(isset($options['clerk_additional_scripts_content'])){
                    $script_js = $options['clerk_additional_scripts_content'];
                } else {
                    $script_js = 's';
                }
                $additional_scripts_html = "<script id='clerk_additional_header_scripts'>$script_js</script>";
                echo $additional_scripts_html;
            }

            if ( isset( $options['livesearch_enabled'] ) && $options['livesearch_enabled'] ) :

                ?>
                <span
                        class="clerk"
                        data-template="@<?php echo esc_attr( strtolower( str_replace( ' ', '-', $options['livesearch_template'] ) ) ); ?>"
                        <?php
                        if ( isset( $options['livesearch_suggestions'] ) && isset( $options['livesearch_include_suggestions'])) :
                            ?>
                                data-instant-search-suggestions="<?php echo $options['livesearch_suggestions']; ?>"
                            <?php
                        endif;
                        if ( isset( $options['livesearch_categories'] ) && isset( $options['livesearch_include_categories'])) :
                            ?>
                                data-instant-search-categories="<?php echo $options['livesearch_categories']; ?>"
                            <?php
                        endif;
                        ?>
                        data-instant-search-positioning="<?php echo strtolower($options['livesearch_dropdown_position']); ?>"
                        <?php
                        if ( isset( $options['livesearch_pages'] ) && isset( $options['livesearch_include_pages'])) :
                        ?>
                            data-instant-search-pages="<?php echo $options['livesearch_pages']; ?>"
                        <?php
                        endif;
                        if ( isset( $options['livesearch_pages_type'] ) && $options['livesearch_pages_type'] != 'All' && isset( $options['livesearch_include_pages'])) :
                            ?>
                            data-instant-search-pages-type="<?php echo $options['livesearch_pages_type']; ?>"
                        <?php
                        endif;
                        if (isset( $options['livesearch_field_selector'] )) :
                        ?>
                        data-instant-search="<?php echo $options['livesearch_field_selector']; ?>">
                        <?php
                        else:
                            ?>
                            data-instant-search=".search-field">
                        <?php
                        endif;
                        ?>
                </span>
            <?php
            endif;

            if ( isset( $options['search_enabled'] ) && $options['search_enabled'] ) :

                ?>
                <script>
                   
                    jQuery(document).ready(function ($) {

                        ClerkSearchPage = function(){

                            $("<?php echo $options['livesearch_field_selector']; ?>").each(function() {
                                $(this).attr('name', 'searchterm');
                                $(this).attr('value', '<?php echo get_search_query() ?>');
                            });
                            $("<?php echo $options['livesearch_form_selector']; ?>").each(function (){
                                $(this).attr('action', '<?php echo esc_url( get_page_link( $options['search_page'] ) ); ?>');
                            });

                            $('input[name="post_type"][value="product"]').each(function (){
                                $(this).remove();
                            });

                        };

                        ClerkSearchPage();

                    });

                </script>
            <?php
            endif;

            if ( isset( $options['collect_baskets'] ) && $options['collect_baskets'] ) :
                
                ?>
                    <script>

                        if(window.hasOwnProperty("jQuery")){
                            // jQuery
                            jQuery(document).ajaxComplete(function(event,request, settings){

                            if( settings.url.includes("add_to_cart") 
                                || settings.url.includes("remove_from_cart") 
                                || settings.url.includes("removed_item")
                                || settings.url.includes("remove_item") 
                                || settings.url.includes("get_refreshed_fragments")
                                ){
            
                                request = jQuery.ajax({
                                                type : "POST",
                                                url  : "/wordpress/wp-admin/admin-ajax.php",
                                                data: {
                                                    action:'get_cart'
                                                }, 
                                            });

                                            request.done(function (response, textStatus, jqXHR){
                                                var clerk_productids = response;
                                                var clerk_last_productids = [];
                                                if( localStorage.getItem('clerk_productids') !== null ){
                                                    clerk_last_productids = localStorage.getItem('clerk_productids').split(",");
                                                    clerk_last_productids = clerk_last_productids.map(Number);  
                                                }
                                                //sort
                                                clerk_productids = clerk_productids.sort((a, b) => a - b);
                                                clerk_last_productids = clerk_last_productids.sort((a, b) => a - b);
                                                // compare
                                                if(JSON.stringify(clerk_productids) == JSON.stringify(clerk_last_productids)){
                                                    // if equal - maybe compare content??
                                                    // console.log('equal: ', clerk_productids, clerk_last_productids)
                                                }else{
                                                    // if not equal send cart to clerk
                                                    //console.log('not equal: ', clerk_productids, clerk_last_productids)
                                                    Clerk('cart', 'set', clerk_productids);
                                                }
                                                // save for next compare
                                                localStorage.setItem("clerk_productids", clerk_productids);
                                            });

                                            request.fail(function (jqXHR, textStatus, errorThrown){  
                                                console.error(
                                                    "The following error occurred: "+
                                                    textStatus, errorThrown
                                                );
                                            });
                                    }   
                            });

                        }else{
                            // no jQuery
                            // Store a reference to the native method
                            let open = XMLHttpRequest.prototype.open; 

                            // Overwrite the native method
                            XMLHttpRequest.prototype.open = function() {
                                // Assign an event listener
                                this.addEventListener("load", function(){

                                if( this.responseURL.includes("add_to_cart") 
                                    || this.responseURL.includes("remove_from_cart") 
                                    || this.responseURL.includes("removed_item") 
                                    || this.responseURL.includes("remove_item") 
                                    || this.responseURL.includes("get_refreshed_fragments")
                                    ){
                                    // get cart here
                                    data = "action=get_cart";

                                    const request = new XMLHttpRequest();

                                    request.addEventListener('load', function () {
                                    if (this.readyState === 4 && this.status === 200) {
                                        var response = this.responseText.replace('[', '').replace(']', '');
                                        var clerk_productids = [];
                                        clerk_productids = response.split(",")
                                        clerk_productids = clerk_productids.map(Number);
                                        var clerk_last_productids = [];
                                        if( localStorage.getItem('clerk_productids') !== null ){
                                            clerk_last_productids = localStorage.getItem('clerk_productids').split(",");
                                            clerk_last_productids = clerk_last_productids.map(Number);  
                                        }
                                        //sort
                                        clerk_productids = clerk_productids.sort((a, b) => a - b);
                                        clerk_last_productids = clerk_last_productids.sort((a, b) => a - b);
                                        // compare
                                        if(JSON.stringify(clerk_productids) == JSON.stringify(clerk_last_productids)){
                                            // if equal - do nothing
                                            // console.log('equal: ', clerk_productids, clerk_last_productids)
                                        }else{
                                            // if not equal send cart to clerk
                                            // console.log('not equal: ', clerk_productids, clerk_last_productids)
                                            Clerk('cart', 'set', clerk_productids);
                                        }
                                        // save for next compare
                                        localStorage.setItem("clerk_productids", clerk_productids);
                                    }
                                    });

                                    request.open('POST', "/wordpress/wp-admin/admin-ajax.php", true);
                                    request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
                                    request.send(data);
                        
                                }

                                }, false);
                                
                                // Call the stored reference to the native method
                                open.apply(this, arguments);
                            };

                        }

                        </script>
                    <?php 
            endif;

        } catch (Exception $e) {

            $this->logger->error('ERROR add_tracking', ['error' => $e->getMessage()]);

        }

    }

    /**
     * Add checkbox to subscribe to newsletter
     */
    public function clerk_woocommerce_review_order_before_submit()
    {

        try {
        $signup_msg = '';
        $options = get_option('clerk_options');
        $show_signup = false;
        if (array_key_exists('collect_emails_signup_message', $options) && array_key_exists('collect_emails', $options)) {
            $signup_msg = $options['collect_emails_signup_message'];
            $show_signup = true;
        }
        if($show_signup):
	?>
		<p class="form-row validate-optional">
		   <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
		   <input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" onclick="subscribeClient();" name="subscribe" id="subscribe">
		   <span class="woocommerce-terms-and-conditions-checkbox-text"><?php echo $signup_msg; ?></span>
		   </label>
		</p>
		<script>
			function subscribeClient(){
				let email_input = document.getElementById('billing_email').value;
				document.getElementById('place_order').addEventListener('click', function(){
					if(email_input.length > 0){
						Clerk("call","subscriber/subscribe", {
						   email: email_input
						});
					}
				});
			}
		</script>
	<?php
        endif;
        } catch (Exception $e) {

            $this->logger->error('ERROR clerk_woocommerce_archive_description', ['error' => $e->getMessage()]);

        }

    }

    /**
     * admin endpiont to get cart contens using ajax
     *
     * @param none
     *
     * @return array
     */
    public function get_cart()
    {
        global $woocommerce;
        $items = $woocommerce->cart->get_cart();
        $_product_ids = [];

        foreach($items as $item => $values) { 
            if (!in_array($values['data']->get_id(), $_product_ids)) {
                array_push($_product_ids, $values['data']->get_id());
            }
        }

        header('Content-Type: application/json');
        wp_die(json_encode($_product_ids));
    }

}

new Clerk_Visitor_Tracking();