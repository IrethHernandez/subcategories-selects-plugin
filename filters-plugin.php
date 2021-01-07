<?php
/*
Plugin Name:  Subcategories Filters Plugin
Plugin URI:   https://github.com/IrethHernandez/subcategories-selects-plugin
Description:  filters by subcategories
Version:      20210107
Author:       Ireth Hernandez
Author URI:   zelda_ale@hotmail.com
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  filter
Domain Path:  /languages
*/

function portfolio_route() {
    register_rest_route( 'categories', '/portfolio-categories/(?P<id>\d+)', array(
        'methods'  => 'GET',
        'callback' => 'get_portfolio_categories',
    ) );
}

function avatars() {
    register_rest_route( 'avatar', '/(?P<id>\d+)', array(
        'methods'  => 'GET',
        'callback' => 'rest_get_avatar',
    ) );
}
   
function get_portfolio_categories($data){
	
    $args = array(
       'echo' => false,
        'taxonomy' => 'category',
        'child_of' => $data['id'],
        'id'=> 'cat-dropbox-'.$data['id'],
        'hierarchical'=> true,
		'class' => 'postform combocat',
        'hide_empty' => true,
        'show_option_all' => 'Todas las categorías'
    );
    return wp_dropdown_categories($args);
}

function rest_get_avatar( $id ) {
    $avatar_sizes = rest_get_avatar_sizes();
 
    $urls = array();
    foreach ( $avatar_sizes as $size ) {
        $urls[ $size ] = get_avatar_url( $id['id'], array( 'size' => $size ) );
    }
    return $urls;
}

 class all_terms
{
    public function __construct()
    {
        $version = '2';
        $namespace = 'wp/v' . $version;
        $base = 'all-terms';
        register_rest_route($namespace, '/' . $base, array(
            'methods' => 'GET',
            'callback' => array($this, 'get_all_terms'),
        ));
    }

    public function get_all_terms($object)
    {
        $return = array();
        // $return['categories'] = get_terms('category');
 //        $return['tags'] = get_terms('post_tag');
        // Get taxonomies
        $args = array(
            '_builtin' => false,
			'show_in_rest' => true,
        );
        $output = 'names'; // or objects
        $operator = 'and'; // 'and' or 'or'
        $taxonomies = get_taxonomies($args, $output, $operator);
        foreach ($taxonomies as $key => $taxonomy_name) {
            if($taxonomy_name = $_GET['term']){
            $return = get_terms(array('orderby' => 'name',
            'order' => 'ASC', 'taxonomy' => $taxonomy_name));
        }
        }
        return new WP_REST_Response($return, 200);
    }
}
add_action( 'rest_api_init', 'portfolio_route' );
add_action( 'rest_api_init', 'avatars' );
add_action('rest_api_init', function () {
    $all_terms = new all_terms;
});

function init_cats(){
	return '<section id="selects-port"></section><button onclick="clearFilters()" class="button-clearcustom-link btn btn-sm border-width-0 btn-color-126667 btn-circle btn-icon-left button-clear">Borrar Filtros</button><section class="cards" id="cards"></section>';
}


add_shortcode('combocats', 'init_cats');

add_action('wp_head', 'load_scripts');

function load_scripts() {
    ?>
        <script>
            fetch('/wp-json/wp/v2/all-terms?term=category')
.then(response => response.json())
.then(results => {
    getCombos(results);
});

let values = [];

function getCombos(results){
    Object.values(results).map((category, index) => {
        if(category.parent === 0){
            getCategories(category, index);
        }
    })
}

function getCategories(category, index){
    fetch(`/wp-json/categories/portfolio-categories/${category.term_id}`)
    .then(response => response.json())
    .then(data => {
       document.getElementById('selects-port').innerHTML += `<div class="combo-cat-gorup"><h5 class="title-combo-categories">${category.name}</h3>${data.replace('<select', '<select onchange="changeCat(this)"')}</div>`;
    });
}

function changeCat(e){
    if(e.value !== 0){
         values.push(e.value);
         doFilter(values)
    }
}

clearFilters();
			
function doFilter(values){
    fetch(`/wp-json/wp/v2/posts?categories=${values}&_embed`)
    .then(response => response.json())
    .then(data => {
        document.getElementById('cards').innerHTML = '';
        cards(data)
    })
}

function clearFilters(){
    values = [];
    fetch(`/wp-json/wp/v2/posts?_embed`)
    .then(response => response.json())
    .then(data => {
        document.getElementById('cards').innerHTML = '';
        cards(data)
    })
	var options = document.querySelectorAll('.combocat option');
	for (var i = 0, l = options.length; i < l; i++) {
		options[i].selected = options[i].defaultSelected;
	}
}

function cards(data){
    data.map(item =>{
		fetch(`/wp-json/avatar/${item.author}`)
		.then(response => response.json())
		.then(avatars =>{
			document.getElementById('cards').innerHTML+= `<article class="card">
				<a href="${item.link}">${item._embedded['wp:featuredmedia'] ? `<img class="image-card" src="${item._embedded['wp:featuredmedia']['0'].source_url}"/>` : `<div></div>`}
				<h5>${item.title.rendered}</h5></a>
				<hr>
				<div class="author-contain">
					<a href="${item._embedded['author']['0'].link}"><img class="author-image" src="${avatars['24']}"/></a>
					<div>
						<p class="author-post"><a href=´${item._embedded['author']['0'].link}´>by ${item._embedded['author']['0'].name}</a></p>
					</div>
				</div>

			</article>`
		})
    })
}
			
		
        </script>
<style>
	.button-clear{
		margin-top: 20px;
	}
	.cards{
		display: grid;
		grid-template-columns: repeat(4, 1fr);
		grid-gap: 36px;
		grid-auto-rows: minmax(100px, auto);
		margin-top: 40px;
		padding-bottom: 20px;
	}
	.card{
		box-shadow: 0px 20px 60px -30px rgba(0, 0, 0, 0.45);
	}
	.card h5{
		font-family: 'Montserrat';
		margin: 9px 0px 0px 0px;
		padding: 10px 20px;
		font-size: 15px;
	}
	
	.card hr{
		border: 0;
		height: 1px;
		background: #eaeaea;
		width: 80%;
		display; block;
		margin-left: auto !important;
		margin-right: auto !important;
		margin-top: 15px;
		margin-bottom: 0;
	}
	
	.author-contain{
		display: flex;
		align-items: center;
		padding: 15px 20px;
	}
	.author-image{
		max-width: 80px !important;
		margin-right: 10px;
	}
	.author-post{
		font-weight: bold;
		margin-top: 0;
	}
	
	.categories-post{
		margin-top: 0;
	}
	
	@media screen and (max-width: 992px){
		.cards{
			grid-template-columns: repeat(3, 1fr);
		}
	}
	@media screen and (max-width: 768px){
		.cards{
			grid-template-columns: repeat(1, 1fr);
		}
	}
	.cards a, .cards a:hover, .cards a:visited, .cards a:active{
		color: #3f3f3f;
	}
	
</style>
    <?php
}