<?php
namespace WpAssetCleanUp;

/**
 * Class Tips
 * @package WpAssetCleanUp
 */
class Tips
{
	/**
	 * @var array
	 */
	public $list = array('css' => array(), 'js' => array());

	/**
	 * Tips constructor.
	 */
	public function __construct()
	{
		// CSS list
		$this->list['css']['wp-block-library'] = <<<HTML
This asset is related to the Gutenberg block editor. If you do not use it (e.g. you have an alternative option such as Divi, Elementor etc.), then it is safe to unload this file.
HTML;

		$this->list['css']['astra-contact-form-7'] = <<<HTML
This asset is related to the "Contact Form 7" plugin. If you do not use it on this page (e.g. only needed on a page such as "Contact"), then you can safely unload it.
HTML;
		$this->list['css']['contact-form-7'] = <<<HTML
This CSS file is related to "Contact Form 7" and if you don't load any form on this page (e.g. you use it only on pages such as Contact, Make a booking etc.), then you can safely unload it (e.g. side-wide and make exceptions on the few pages you use it).
HTML;

		// JavaScript list
		$this->list['js']['wp-embed'] = <<<HTML
To completely disable oEmbeds, you can use "Disable oEmbed (Embeds) Site-Wide" from plugin's "Settings" -&gt; "Site-Wide Common Unloads". It will also prevent this file from loading in the first place and hide it from this location.
HTML;
		$this->list['js']['wc-cart-fragments'] = <<<HTML
This is used to make an AJAX call to retrieve the latest WooCommerce cart information. If there is no cart area (e.g. in a sidebar or menu), you can safely unload this file.
HTML;

		$this->list['js']['contact-form-7'] = <<<HTML
This JavaScript file is related to "Contact Form 7" and if you don't load any form on this page (e.g. you use it only on pages such as Contact, Make a booking etc.), then you can safely unload it (e.g. side-wide and make exceptions on the few pages you use it).
HTML;
	}
}
