<?php
if (!defined('_PS_VERSION_'))
    exit;

/*
 * ShopALike export tool (XML only)
 * @prestashopVersion 1.5.31
 * Japeon
 * API
 * Based on idea:
 * (http://www.igwane.com/fr/license)
 */

class ShopALikeXML extends Module
{
    function __construct()
    {
        $this->name    = 'shopalikexml';
        $this->tab     = 'export';
        $this->version = '1.0';
        $this->author  = 'JAPEON - JAPPSOFT SL';
        
        parent::__construct();
        
        $this->page        = basename(__FILE__, '.php');
        $this->displayName = $this->l('Exportador ShopALike');
        $this->description = $this->l('Export products for ShopALike');
        
        $this->need_instance          = 0;
        $this->ps_versions_compliancy = array(
            'min' => '1.5',
            'max' => '1.6'
        );
        
        $this->uri = ToolsCore::getCurrentUrlProtocolPrefix() .$this->context->shop->domain_ssl.$this->context->shop->physical_uri;
    }
    
    function install()
    {
        if (!parent::install()) {
            return false;
        }
        return true;
    }
    
    
    public function getContent()
    {
        if (isset($_POST['generate'])) {
        	//shipping price
            if (isset($_POST['shipping'])) {
                Configuration::updateValue('SL_SHIPPING', $_POST['shipping']);
                
            }
	    
	    if (isset($_POST['shipping_free'])) {
                Configuration::updateValue('SL_SHIPPING_FREE', $_POST['shipping_free']);
                
            }
	    //Shipping out of your country
	    if (isset($_POST['shipping_ex'])) {
                Configuration::updateValue('SL_SHIPPING_EX', $_POST['shipping_ex']);
                
            }
            //image type
            if (isset($_POST['image'])) {
                Configuration::updateValue('SL_IMAGE', $_POST['image']);
            }
            
            //country
            if (isset($_POST['country'])) {
                Configuration::updateValue('SL_COUNTRY', $_POST['country']);
            }
            
            
            // Get installed languages
            $languages = Language::getLanguages();
            foreach ($languages as $i => $lang) {
                if (isset($_POST['product_type_' . $lang['iso_code']])) {
                    Configuration::updateValue('SL_PRODUCT_TYPE_' . $lang['iso_code'], $_POST['product_type_' . $lang['iso_code']]);
                }
		if (isset($_POST['category_excl_' . $lang['iso_code']])) {
                    Configuration::updateValue('SL_CATEGORY_EXCL_' . $lang['iso_code'], $_POST['category_excl_' . $lang['iso_code']]);
                }
            }
            
            
            // Get generation file route
            if (isset($_POST['generate_root']) && $_POST['generate_root'] === "on") {
                Configuration::updateValue('GENERATE_FILE_IN_ROOT_SL', intval(1));
                
            } else {
                Configuration::updateValue('GENERATE_FILE_IN_ROOT_SL', intval(0));
                @mkdir($path_parts["dirname"] . '/file_exports', 0755, true);
                @chmod($path_parts["dirname"] . '/file_exports', 0755);
            }

            // QTY
            if (isset($_POST['quantity']) && $_POST['quantity'] === "on") {
                Configuration::updateValue('QUANTITY_SL', intval(1));
            } else {
                Configuration::updateValue('QUANTITY_SL', intval(0));
            }
            // GENDER FIRST
	    if (isset($_POST['gender_first']) && $_POST['gender_first'] === "on") {
                Configuration::updateValue('GENDER_FIRST', intval(1));
            } else {
                Configuration::updateValue('GENDER_FIRST', intval(0));
            }
            // Brand
            if (isset($_POST['brand']) && $_POST['brand'] === "on") {
                Configuration::updateValue('BRAND_SL', intval(1));
            } else {
                Configuration::updateValue('BRAND_SL', intval(0));
            }
	    // Inactive products
            if (isset($_POST['inactive']) && $_POST['inactive'] === "on") {
                Configuration::updateValue('INACTIVE_SL', intval(1));
            } else {
                Configuration::updateValue('INACTIVE_SL', intval(0));
            }
            // Description
            if (isset($_POST['description']) && $_POST['description'] != 0) {
                Configuration::updateValue('DESCRIPTION_SL', intval($_POST['description']));
            }

            //Category shop
            if (isset($_POST['category_shop']) && $_POST['category_shop'] === "on") {
                Configuration::updateValue('CATEGORY_SHOP_SL', intval(1));
            } else {
                Configuration::updateValue('CATEGORY_SHOP_SL', intval(0));
            }
            
            $this->generateFileList();
        }
        
        $output = '<h2>' . $this->displayName . '</h2>';
        $output .= $this->_displayForm();
        
        // Link to generated files
        $output .= '<fieldset class="space width3">
						<legend>' . $this->l('Files') . '</legend>
						<p><b>' . $this->l('Generated link files') . '</b></p>';
        
        // Get active langs on shop
        $languages = Language::getLanguages();
        
        
        foreach ($languages as $i => $lang) {
            if (Configuration::get('GENERATE_FILE_IN_ROOT') == 1) {
                $get_file_url = $this->uri. 'shopalike-' . $lang['iso_code'] . '.xml';
            } else {
                $get_file_url = $this->uri. 'modules/' . $this->getName() . '/file_exports/shopalike-' . $lang['iso_code'] . '.xml';
            }
            
            $output .= '<a href="' . $get_file_url . '">' . $get_file_url . '</a><br />';
        }
        
        $output .= '<hr>';
        $output .='<p><b>'.$this->l('Automatic file generation').'</b></p>';
		$output .= $this->l('Install a CRON rule to update the feed frequently');
		$output .= '<br/>';
		$output .= $this->uri. 'modules/' . $this->getName() . '/cron.php' . '</p>';
		$output .= '</fieldset>';
        
        
        return $output;
    }
    
    private function _displayForm()
    {
        
        $options               = '';
        $mpn                   = '';
        $generate_file_in_root = '';
        $quantity              = '';
        $brand                 = '';
        $inactive              = '';
        $selected_short        = '';
        $selected_long         = '';
        $gender_first	       = '';
        $category_shop         = '';
	$shipping_free         = '';
        
        // Check if you want generate file on root
        if (Configuration::get('GENERATE_FILE_IN_ROOT') == 1) {
            $generate_file_in_root = "checked";
        }
        
        // googleshopping optional tags
        if (Configuration::get('GENDER_FIRST') == 1) {
            $gender_first = "checked";
        }
	if (Configuration::get('INACTIVE_SL') == 1) {
            $inactive = "checked";
        }
        if (Configuration::get('QUANTITY_SL') == 1) {
            $quantity = "checked";
        }
        if (Configuration::get('BRAND_SL') == 1) {
            $brand = "checked";
        }
        if (Configuration::get('CATEGORY_SHOP_SL') == 1) {
            $category_shop = "checked";
        }
        
        (intval(Configuration::get('DESCRIPTION_SL')) === intval(1)) ? $selected_short = "selected" : $selected_long = "selected";
        
        $form = '
		<form action="' . $_SERVER['REQUEST_URI'] . '" method="post">


		<fieldset style="float: right; width: 255px">
					<legend>' . $this->l('About') . '</legend>
					<p style="font-size: 1.5em; font-weight: bold; padding-bottom: 0">' . $this->displayName . ' ' . $this->version . '</p>
					<p style="clear: both">
					' . $this->description . '
					</p>
		</fieldset>

		<fieldset class="space width3">
		<legend>' . $this->l('Parameters') . '</legend>';
        
        $form .= '
			<label>' . $this->l('Description Type') . ' </label>
			<div class="margin-form">
				<select name="description">
					<option value="1" ' . $selected_short . '>' . $this->l('Short Description') . '</option>
					<option value="2" ' . $selected_long . '>' . $this->l('Long Description') . '</option>
				</select>
			</div>';
        
        // Récupération des langues actives pour la boutique
        $languages = Language::getLanguages();
        foreach ($languages as $i => $lang) {
            $form .= '<label title="product_type_' . $lang['iso_code'] . '">' . $this->l('Category') . ' ' . strtoupper($lang['iso_code']) . '</label>
			<div class="margin-form">
				<input type="text" name="product_type_' . $lang['iso_code'] . '" value="' . Configuration::get('SL_PRODUCT_TYPE_' . $lang['iso_code']) . '" size="40">
			</div>';
	    $form .= '<label title="category_excl_' . $lang['iso_code'] . '">' . $this->l('Category excluded') . ' ' . strtoupper($lang['iso_code']) . '</label>
			<div class="margin-form">
				<input type="text" name="category_excl_' . $lang['iso_code'] . '" value="' . Configuration::get('SL_CATEGORY_EXCL_' . $lang['iso_code']) . '" size="40">
			</div>';
        }

        
        $form .= '<label title="[shipping]">' . $this->l('Shipping') . ' </label>
			<div class="margin-form">
				<input type="text" name="shipping" value="' . Configuration::get('SL_SHIPPING') . '">
			</div>
			<label title="[shipping_free]">' . $this->l('Free shipping for order over...') . ' </label>
			<div class="margin-form">
				<input type="text" name="shipping_free" value="' . Configuration::get('SL_SHIPPING_FREE') . '">
			</div>
		    <label title="[shipping_ex]">' . $this->l('Shipping out of your country') . ' </label>
			<div class="margin-form">
				<input type="text" name="shipping_ex" value="' . Configuration::get('SL_SHIPPING_EX') . '">
			</div>
			<label title="[country]">' . $this->l('Shipping Country') . ' </label>
			<div class="margin-form">
				<input type="text" name="country" value="' . ((Configuration::get('SL_COUNTRY') != '') ? (Configuration::get('SL_COUNTRY')) : 'ES') . '">
			</div>
			
			<label title="[image]">' . $this->l('Image Type') . ' </label>
			<div class="margin-form">
				<input type="text" name="image" value="' . ((Configuration::get('SL_IMAGE') != '') ? (Configuration::get('SL_IMAGE')) : 'large_default') . '">
			</div>

			<hr>

			<table>
				<tr>
					<td><label>' . $this->l('Generate the files to the root of the site') . '</label></td>
					<td><input type="checkbox" name="generate_root" ' . $generate_file_in_root . '></td>
				</tr>
				<tr>
					<td><label>' . $this->l('Use first Category as gender') . '</label></td>
					<td><input type="checkbox" name="gender_first" ' . $gender_first . '></td>
				</tr>
				<tr>
					<td><label>' . $this->l('Include inactive products') . '</label></td>
					<td><input type="checkbox" name="inactive" ' . $inactive . '></td>
				</tr>
				<tr>
					<td><label>' . $this->l('Categories breadcrumb shop') . '</label></td>
					<td><input type="checkbox" name="category_shop" ' . $category_shop . '></td>
				</tr>
				<tr>
					<td><label>' . $this->l('Number of products') . '</label></td>
					<td><input type="checkbox" name="quantity" ' . $quantity . ' title="' . $this->l('Recomended') . '"></td>
				</tr>
				<tr>
					<td><label title="[brand]">' . $this->l('Brand') . '</label></td>
					<td><input type="checkbox" name="brand" ' . $brand . ' title="' . $this->l('Recomended') . '"></td>
				</tr>
			</table>
			<br>
			<center><input name="generate" type="submit" value="' . $this->l('Generate') . '"></center>
		</fieldset>
		</form>
		';
        return $form;
    }
    
    public function getName()
    {
        $output = $this->name;
        return $output;
    }
    
    public function uninstall()
    {
        Configuration::deleteByName('SL_PRODUCT_TYPE');
        Configuration::deleteByName('SL_SHIPPING');
	Configuration::deleteByName('SL_SHIPPING_EX');
	Configuration::deleteByName('SL_SHIPPING_FREE');
        Configuration::deleteByName('SL_COUNTRY');
        return parent::uninstall();
    }
    
    public function generateFileList()
    {
        // Get all shop languages
        $languages = Language::getLanguages();
        foreach ($languages as $i => $lang) {
            $this->generateFile($lang);
        }
    }
    
private function rip_tags($string) { 
    
    // ----- remove HTML TAGs ----- 
    $string = preg_replace ('/<[^>]*>/', ' ', $string); 
    
    // ----- remove control characters ----- 
    $string = str_replace("\r", '', $string);    // --- replace with empty space
    $string = str_replace("\n", ' ', $string);   // --- replace with space
    $string = str_replace("\t", ' ', $string);   // --- replace with space
    
    // ----- remove multiple spaces ----- 
    $string = trim(preg_replace('/ {2,}/', ' ', $string));
    
    return $string; 

}
    
    private function generateFile($lang)
    {
        $path_parts = pathinfo(__FILE__);
        
        if (Configuration::get('GENERATE_FILE_IN_ROOT_SL')):
            $generate_file_path = '../shopalike-' . $lang['iso_code'] . '.xml';
        else:
            $generate_file_path = $path_parts["dirname"] . '/file_exports/shopalike-' . $lang['iso_code'] . '.xml';
        endif;
        
        //ShopALike XML file
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>' . "\n";
        $xml .= '<items>' . "\n";
        
        $shopalikefile = fopen($generate_file_path, 'w');
        $excluye  = '';
        fwrite($shopalikefile, $xml);
        if (Configuration::get('SL_SHIPPING_FREE')) {$inactivo=''; } else {$inactivo = ' AND p.active = 1 AND ';}
	// Category Shopalike
            if (Configuration::get('SL_CATEGORY_EXCL_' . $lang['iso_code'])) {
		$cat_excl = Configuration::get('SL_CATEGORY_EXCL_' . $lang['iso_code']);
                $categ_excl=explode(",", $cat_excl);
		foreach ($categ_excl as $ca) {
		   $excluye .=' AND id_category_default !=' .$ca;
		}
		
            }
        
//	$sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'product p' . ' LEFT JOIN ' . _DB_PREFIX_ . 'product_lang pl ON p.id_product = pl.id_product' . ' WHERE '. $inactivo .' pl.id_lang=' . $lang['id_lang'] . $excluye;
//fwrite($shopalikefile, $sql);
//SELECT * FROM ps_product p LEFT JOIN ps_product_lang pl ON p.id_product = pl.id_product WHERE pl.id_lang=4 
//ID Feature Material y Composicion 12 y 2, Color
//SELECT * FROM `ps_feature_value` fv LEFT JOIN `ps_feature_value_lang` fvl ON fv.id_feature_value=fvl.id_feature_value LEFT JOIN ps_feature_product pfp ON fv.id_feature=pfp.id_feature WHERE (fv.id_feature = 2 OR fv.id_feature=12) and id_lang=4 GROUP BY id_product
//$sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'product p LEFT JOIN ' ._DB_PREFIX_ .'product_lang pl ON p.id_product=pl.id_product LEFT JOIN ';
//$sql.= _DB_PREFIX_ .'feature_value fv LEFT JOIN '._DB_PREFIX_.'feature_value_lang fvl ON fv.id_feature_value=fvl.id_feature_value LEFT ';
//$sql.= 'JOIN '._DB_PREFIX_.'feature_product pfp ON fv.id_feature=pfp.id_feature ON pfp.id_product=p.id_product WHERE '. $inactivo .' pl.id_lang=' . $lang['id_lang'] . $excluye;
//$sql.= ' AND (fv.id_feature=2 OR fv.id_feature=12) and fvl.id_lang='. $lang['id_lang'] .' GROUP BY p.id_product';
//SELECT * FROM (SELECT p.id_product, p.active, p.id_category_default, p.quantity, p.date_upd, p.reference, pl.name, pl.description, p.price, pl.link_rewrite, p.ean13 FROM ps_product p LEFT JOIN ps_product_lang pl ON p.id_product = pl.id_product WHERE pl.id_lang=4 GROUP BY p.id_product) prod LEFT JOIN (SELECT pfp.id_product, pfp.id_feature, pfp.id_feature_value, pfvl.value, pfvl.id_lang FROM ps_feature_product pfp LEFT JOIN ps_feature_value_lang pfvl on pfp.id_feature_value=pfvl.id_feature_value WHERE (pfp.id_feature=2 OR pfp.id_feature=12 or pfp.id_feature=9 or pfp.id_feature=15) AND pfvl.id_lang=4) pfpa on pfpa.id_product=prod.id_product 

//SELECT * FROM (SELECT p.id_product, p.active, p.id_category_default, p.quantity, p.date_upd, p.reference, pl.name, pl.description, p.price, pl.link_rewrite, p.ean13 FROM ps_product p LEFT JOIN ps_product_lang pl ON p.id_product = pl.id_product WHERE pl.id_lang=4 GROUP BY p.id_product) prod LEFT JOIN (SELECT pfp.id_product, pfp.id_feature, pfp.id_feature_value, pfvl.value, pfvl.id_lang FROM ps_feature_product pfp LEFT JOIN ps_feature_value_lang pfvl on pfp.id_feature_value=pfvl.id_feature_value WHERE (pfp.id_feature=2 OR pfp.id_feature=12 or pfp.id_feature=9 or pfp.id_feature=15) AND pfvl.id_lang=4) pfpa on pfpa.id_product=prod.id_product LEFT JOIN (SELECT pfp.id_product, pfp.id_feature, pfp.id_feature_value, pfvl.value, pfvl.id_lang FROM ps_feature_product pfp LEFT JOIN ps_feature_value_lang pfvl on pfp.id_feature_value=pfvl.id_feature_value WHERE (pfp.id_feature=20) AND pfvl.id_lang=4) pfpa2 on pfpa2.id_product=prod.id_product

//SELECT * FROM (SELECT p.id_product, p.active, p.id_category_default, p.quantity, p.date_upd, p.reference, pl.name, pl.description, p.price, pl.link_rewrite, p.ean13 FROM
//ps_product p LEFT JOIN ps_product_lang pl ON p.id_product = pl.id_product WHERE pl.id_lang=4 GROUP BY p.id_product) prod LEFT JOIN
//(SELECT pfp.id_product, pfp.id_feature, pfp.id_feature_value, pfvl.value, pfvl.id_lang FROM ps_feature_product pfp LEFT JOIN ps_feature_value_lang pfvl on pfp.id_feature_value=pfvl.id_feature_value WHERE (pfp.id_feature=2 OR pfp.id_feature=12 or pfp.id_feature=9 or pfp.id_feature=15) AND pfvl.id_lang=4) pfpa on pfpa.id_product=prod.id_product LEFT JOIN
//(SELECT pfp.id_product, pfp.id_feature, pfp.id_feature_value, pfvl.value as color, pfvl.id_lang FROM ps_feature_product pfp LEFT JOIN ps_feature_value_lang pfvl on pfp.id_feature_value=pfvl.id_feature_value WHERE (pfp.id_feature=20) AND pfvl.id_lang=4) pfpa2 on pfpa2.id_product=prod.id_product

$sql = 'SELECT * FROM (SELECT p.id_product, p.active, p.id_category_default, p.id_manufacturer, p.quantity, p.date_upd, p.reference, pl.name, pl.description, pl.description_short, p.price, pl.link_rewrite, pl.id_lang, p.ean13 FROM ';
$sql.= _DB_PREFIX_.'product p LEFT JOIN '._DB_PREFIX_.'product_lang pl ON p.id_product = pl.id_product WHERE pl.id_lang='. $lang['id_lang'] .' GROUP BY p.id_product) prod LEFT JOIN ';
$sql.= '(SELECT pfp.id_product, pfp.id_feature, pfp.id_feature_value, pfvl.value, pfvl.id_lang FROM '._DB_PREFIX_.'feature_product pfp LEFT JOIN '._DB_PREFIX_.'feature_value_lang pfvl on pfp.id_feature_value=pfvl.id_feature_value WHERE (pfp.id_feature=2 OR pfp.id_feature=12 or pfp.id_feature=9 or pfp.id_feature=15) AND pfvl.id_lang='. $lang['id_lang'] .') pfpa on pfpa.id_product=prod.id_product LEFT JOIN ';
$sql.= '(SELECT pfp.id_product, pfp.id_feature, pfp.id_feature_value, pfvl.value as color, pfvl.id_lang FROM '._DB_PREFIX_.'feature_product pfp LEFT JOIN '._DB_PREFIX_.'feature_value_lang pfvl on pfp.id_feature_value=pfvl.id_feature_value WHERE (pfp.id_feature=20) AND pfvl.id_lang='. $lang['id_lang'] .') pfpa2 on pfpa2.id_product=prod.id_product';
$sql.= ' WHERE 1 '. $inactivo .' '. $excluye;
//fwrite($shopalikefile, $sql);
//print_r($sql);
	$products = Db::getInstance()->ExecuteS($sql);
                
        $title_limit       = 70;
        $description_limit = 10000;
        
        $languages     = Language::getLanguages();
        $tailleTabLang = sizeof($languages);
        
        foreach ($products as $product) {
	    //print_r(array_keys($product));
            $xml_shopalike = '';
            $cat_link_rew       = Category::getLinkRewrite($product['id_category_default'], intval($lang));
	    if (Configuration::get('QUANTITY_SL') == 1) {$quantity = StockAvailable::getQuantityAvailableByProduct($product['id_product'], 0);}

    	    if ($quantity>0) {
            //continue if product not have price
	    	    
            $price = Product::getPriceStatic($product['id_product'], true, NULL, 2);
	    //print_r($price);
            if (empty($price)) {
                continue;
            }
 
            $product_link = $this->context->link->getProductLink((int) ($product['id_product']), $product['link_rewrite'], $cat_link_rew, $product['ean13'], (int) ($product['id_lang']), 1, 0, true);
            
            $title_crop = $product['name'];
            if (strlen($product['name']) > $title_limit) {
                $title_crop = substr($title_crop, 0, ($title_limit - 1));
                $title_crop = substr($title_crop, 0, strrpos($title_crop, " "));
            }
            
            if (intval(Configuration::get('DESCRIPTION_SL')) === intval(2)) {
                $description_crop = $product['description'];
            } else {
                $description_crop = $product['description_short'];
            }
            $description_crop =$this->rip_tags($description_crop);
            
            if (strlen($description_crop) > $description_limit) {
                $description_crop = substr($description_crop, 0, ($description_limit - 1));
                $description_crop = substr($description_crop, 0, strrpos($description_crop, " "));
            }
            $xml_shopalike .= '<item>' . "\n";
            $xml_shopalike .= '<itemId>' . $product['id_product'] . '-' . $lang['iso_code'] . '</itemId>' . "\n";
            $xml_shopalike .= '<name>' . htmlspecialchars(ucfirst(mb_strtolower($title_crop, 'UTF-8'))) . '</name>' . "\n";
            $xml_shopalike .= '<deepLink>' . $product_link . '</deepLink>' . "\n";
            $xml_shopalike .= '<price>' . $price . '</price>' . "\n";
            $xml_shopalike .= '<description>' . htmlspecialchars($description_crop, null, 'UTF-8', false) . '</description>' . "\n";
            $xml_shopalike .= '<currency>EUR</currency>' . "\n"; // currency = EUR, USD, GBP
        
            // Pour chaque image
            $images       = Image::getImages($lang['id_lang'], $product['p.id_product']);
            $indexTabLang = 0;
            
            if ($tailleTabLang > 1) {
                while (sizeof($images) < 1 && $indexTabLang < $tailleTabLang) {
                    if ($languages[$indexTabLang]['id_lang'] != $lang['id_lang']) {
                        $images = Image::getImages($languages[$indexTabLang]['id_lang'], $product['id_product']);
                    }
                    $indexTabLang++;
                }
            }
            
            $nbimages   = 0;
            $image_type = Configuration::get('SL_IMAGE');
            if ($image_type == '')
                $image_type = 'thickbox_default';
            
            /* create image links */
            foreach ($images as $im) {
                $image = $this->context->link->getImageLink($product['link_rewrite'], $product['p.id_product'] . '-' . $im['id_image'], $image_type);
                $xml_shopalike .= '<image>' . $image . '</image>' . "\n";
                //max images by product
                if (++$nbimages == 10)
                    break;
            }
            
            if (Configuration::get('QUANTITY_SL') == 1) {
            	$quantity = StockAvailable::getQuantityAvailableByProduct($product['p.id_product'], 0);
                if ($quantity>0)
                {
                	$xml_shopalike .= '<availability>in stock</availability>'."\n";
                }
                else{
                	$xml_shopalike .= '<availability>out of stock</availability>'."\n";
                }
            }
            
            // Brand
            if (Configuration::get('BRAND') && $product['id_manufacturer'] != '0') {
                $xml_shopalike .= '<brand>' . htmlspecialchars('Errante', null, 'UTF-8', false) . '</brand>' . "\n";
            }
            
            // Category Shopalike
            if (Configuration::get('SL_PRODUCT_TYPE_' . $lang['iso_code'])) {
                $product_type = str_replace('>', '&gt;', Configuration::get('SL_PRODUCT_TYPE_' . $lang['iso_code']));
                $product_type = str_replace('&', '&amp;', $product_type);
                $xml_shopalike .= '<fullCategory>' . $product_type . '</fullCategory>' . "\n";
		$xml_shopalike .= '<category>Ropa y accesorios</category>' . "\n";
            }
	$os = array("Hombre", "Mujer", "Homme", "Femme", "Woman", "Men");
            // Category shop
            if (Configuration::get('CATEGORY_SHOP_SL')){
                $categories = $this->getBreadcrumbCategory($product['id_category_default'], $product['id_lang']);
                //$categories = str_replace('>', '&gt;', $categories);
                //$categories = str_replace('&', '&amp;', $categories);
		$categories = substr($categories,0, strlen($categories)-3);
		$categ = explode(" > ", $categories);
		if (!in_array($categ[0],$os)) {$categ[0]="Unisex";}
		if (Configuration::get('GENDER_FIRST')) {$xml_shopalike .= '<gender>' .$categ[0] .'</gender>'. "\n";$xml_shopalike .= '<fullCategory>' . $categ[1] . '</fullCategory>' . "\n";}
		else{$xml_shopalike .= '<fullCategory>' . $categ[0] . '</fullCategory>' . "\n";$xml_shopalike .= '<category>' . $categ[1] . '</category>' . "\n";}
                unset($categ);
            }
            //Material taken directly from the Feature table
	    // id_feature = 2 or 12 or 9 or 15
	    //if ($product['id_feature']==2 OR $product['id_feature']==12 OR $product['id_feature']==9 OR $product['id_feature']==15)  {		
		$tem=$product['value'];
		$tem= ereg_replace("[^A-Za-záéíóúñ]", " ", $tem);
		$tem=trim(ucwords($tem));
		$atem=explode(" ",$tem);
		$tem=$atem[0];
		if ($lang['iso_code']=='es') {$os2 = array('Seda','Terciopelo','Poliéster','Algodón','Viscosa','Plumas','Ágatha', 'Cuarzo', 'Metal', 'Papel', 'Cristal', 'Madera', 'Concha','Selenita','Sal','Lana');}else
		    {$os2 = array('Soie','Velours','Polyester','Coton','Viscose','Plumes','Agatha', 'Quartz', 'Ultraléger', 'Papier', 'Verre', 'Bois', 'Coquille','Sélénite','Sel','Lana');}
		$matorder=array_search($tem, $os2);
		$os_es = array('Seda','Terciopelo', 'Poliéster', 'Algodón-Modal','Viscosa-Algodón','Plumas','Ágatha','Cuarzo','Metal ultraligero', 'Papel maché', 'Cristal de Murano', 'Madera', 'Concha natural', 'Selenita', 'Sal del Himalaya','Lana');
		$os_fr = array('Soie','Velours', 'Polyester', 'Coton-Modal','Viscose-Coton','Plumes','Agatha','Quartz','Ultraléger métal', 'Papier mâché', 'Verre de Murano', 'Bois', 'Coquille naturelle', 'Sélénite', 'Sel de l´Himalaya','Lana');
		$xml_shopalike .= '<material>';
		if ($lang['iso_code']=='es') {$xml_shopalike .= htmlspecialchars($os_es[$matorder], null, 'UTF-8', false) .'</material>' ."\n";}else
		{$xml_shopalike .= htmlspecialchars($os_fr[$matorder], null, 'UTF-8', false) .'</material>' ."\n";}
	    //}
	    
            //Shipping
	    if ($price >Configuration::get('SL_SHIPPING_FREE')) {
		if ($lang['iso_code']=='es') {
		    $xml_shopalike .= '<shippingCosts>0.00</shippingCosts>' . "\n";
		}else{
		    $xml_shopalike .= '<shippingCosts>' . Configuration::get('SL_SHIPPING_EX') . '</shippingCosts>' . "\n";
		}
	    }else{
		if ($lang['iso_code']=='es') {
		    $xml_shopalike .= '<shippingCosts>' . Configuration::get('SL_SHIPPING') . '</shippingCosts>' . "\n";
		}else{
		    $xml_shopalike .= '<shippingCosts>' . Configuration::get('SL_SHIPPING_EX') . '</shippingCosts>' . "\n";
		}
	    }
	    
	    //Last modification
	    $xml_shopalike .= '<lastModified>'.$product['date_upd'] .'</lastModified>'. "\n";
            $xml_shopalike .= '<color>'.$product['color'].'</color>';
            $xml_shopalike .= '</item>' . "\n";
            
            // Ecriture du produit dans l'XML googleshopping
            fwrite($shopalikefile, $xml_shopalike);
	    }
        }
        
        $xml = '</items>';
        fwrite($shopalikefile, $xml);
        fclose($shopalikefile);
        
        @chmod($generate_file_path, 0777);
        return true;
    }

    private function getBreadcrumbCategory($id_category, $id_lang = null)
    	{
    		$context = Context::getContext()->cloneContext();
    		$context->shop = clone($context->shop);

    		if (is_null($id_lang))
    			$id_lang = $context->language->id;

    		$categories = '';
    		$id_current = $id_category;
    		if (count(Category::getCategoriesWithoutParent()) > 1 && Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE') && count(Shop::getShops(true, null, true)) != 1)
    			$context->shop->id_category = Category::getTopCategory()->id;
    		elseif (!$context->shop->id)
    			$context->shop = new Shop(Configuration::get('PS_SHOP_DEFAULT'));
    		$id_shop = $context->shop->id;
    		while (true)
    		{
    			$sql = '
    			SELECT c.*, cl.*
    			FROM `'._DB_PREFIX_.'category` c
    			LEFT JOIN `'._DB_PREFIX_.'category_lang` cl
    				ON (c.`id_category` = cl.`id_category`
    				AND `id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('cl').')';
    			if (Shop::isFeatureActive() && Shop::getContext() == Shop::CONTEXT_SHOP)
    				$sql .= '
    			LEFT JOIN `'._DB_PREFIX_.'category_shop` cs
    				ON (c.`id_category` = cs.`id_category` AND cs.`id_shop` = '.(int)$id_shop.')';
    			$sql .= '
    			WHERE c.`id_category` = '.(int)$id_current;
    			if (Shop::isFeatureActive() && Shop::getContext() == Shop::CONTEXT_SHOP)
    				$sql .= '
    				AND cs.`id_shop` = '.(int)$context->shop->id;
    			$root_category = Category::getRootCategory();
    			if (Shop::isFeatureActive() && Shop::getContext() == Shop::CONTEXT_SHOP &&
    				(!Tools::isSubmit('id_category') ||
    					(int)Tools::getValue('id_category') == (int)$root_category->id ||
    					(int)$root_category->id == (int)$context->shop->id_category))
    				$sql .= '
    					AND c.`id_parent` != 0';

    			$result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

                if (!$result || ($result[0]['id_category'] == $context->shop->id_category))
             		return $categories;

    			if (isset($result[0]))
    				$categories = $result[0]['name'].' > '.$categories;

    			$id_current = $result[0]['id_parent'];
    		}
		
    	}
}
?>
