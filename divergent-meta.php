<?php 
/** 
 * This class is responsible for controlling the display of metaboxes
 * 
 * @author Michiel
 * @since 1.0.0
 */
namespace Classes\Divergent;

// Bail if accessed directly
if ( ! defined( 'ABSPATH' ) ) 
    die;

class Divergent_Meta extends Divergent_Abstract {    
    
    /**
     * Contains the $metaBox array for each of the option pages
     */
    protected $metaBox;
    
    /**
     * Constructor
     */    
    protected function initialize() {
        $this->metaBox  = $this->params;
        $this->type     = isset( $this->metaBox['type'] ) ? $this->metaBox['type'] : 'post';
    }   
    
    /**
     * Register WordPress Hooks
     */
    protected function registerHooks() {
        
        // Post type metabox
        if( $this->type == 'post' ) {
            $this->actions = array(
                array('add_meta_boxes', 'add'),
                array('save_post', 'save', 10, 1),
            );
        }
        
        // Taxonomy metabox
        if( $this->type == 'taxonomy' ) {
            $this->actions = array(
                array( $this->metaBox['taxonomy'] . '_edit_form_fields', 'add' ),   
                array( 'edited_' . $this->metaBox['taxonomy'], 'save', 10, 1 )
            );
        }
      
    }
    
    /**
     * Adds the specific metaboxes to a certain post or any other type
     */    
    public function add() {
        
        // We should have an id
        if( ! isset($metabox['id']) )
            return;
        
        // Post type metabox
        if( $this->type == 'post' ) {
            add_meta_box( 
                $this->metaBox['id'], 
                $this->metaBox['title'], 
                array( $this, 'render' ), 
                $this->metaBox['post_type'], 
                $this->metaBox['context'], 
                $this->metaBox['priority'], 
                $this->metaBox
            );
        }
        
    }
    
    /**
     * Callback for rendering the specific metaboxes, using any of the specified classes.
     *
     * @param object    $object     The post, term or user object for the current post type
     * @param array     $metabox    The array passed through the callback function in $this->add
     */
    public function render( $object, $metabox ) {
        
        $values                 = get_post_meta($post->ID, $metabox['id'], true);
        
        $frame                  = new Divergent_Frame( $this->metaBox, $values );
        $frame->settingsFields  = wp_nonce_field( 'divergent-metaboxes', 'divergent-metaboxes-nonce', true, false );
        
        $frame->render();
        
        return;
        
    }
    
    /**
     * Callback for saving the specific metaboxes
     *
     * @param int $id The id for the current object we are saving
     */      
    public function save( $id ) {
    
        // Do not save on autosaves
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
            return $id; 

        // Check our user capabilities
        if ( ! current_user_can( 'edit_posts', $post_id ) || ! current_user_can( 'edit_page', $post_id ) )
            return $id;
         
        // Check our nonces
        if ( ! wp_verify_nonce( $_POST['divergent-metaboxes-nonce'], 'divergent-metaboxes' ) ) 
            return $id;
        
        // Retrieve our current meta values
        $current    = get_metadata( $this->type, $id, $this->metaBox['id'], true ); 
        $output     = Divergent_Validate::format( $this->metaBox, $_POST );
        
        // Return if nothing has changed
        if( $current == $output )
            return;
        
        // Delete metadata if the output is empty
        if( empty($output) ) {
            delete_metadata( $this->type, $id, $this->metaBox['id']);
            return;
        } 
        
        // Update meta data
        update_metadata( $this->type, $id, $this->metaBox['id'], $output);     
        
    }
    
}