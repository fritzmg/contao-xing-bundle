<?php

/**
 * Contao Open Source CMS, Copyright (C) 2005-2016 Leo Feyer
 *
 * @copyright  Glen Langer 2008..2016 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    Xing
 * @license    LGPL
 * @filesource
 * @see	       https://github.com/BugBuster1701/contao-xing-bundle
 */

/**
 * Run in a custom namespace, so the class can be replaced
 */
namespace BugBuster\Xing;
use BugBuster\Xing\XingImage;
use Psr\Log\LogLevel;
use Contao\CoreBundle\Monolog\ContaoContext;

/**
 * Class ModuleXingList
 *
 * @copyright  Glen Langer 2008..2016
 * @author     Glen Langer (BugBuster)
 * @package    Xing
 */
class ModuleXingList extends \Module
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_xing_list';

	/**
	 * Category
	 * @var array
	 */
	protected $xing_category = array();

	/**
	 * Xing Image Link
	 * @var string
	 */
	protected $xing_images = '';

	/**
	 * Current version of the class.
	 */
	const XINGLIST_VERSION = '1.1.0';


	/**
	 * Display a wildcard in the back end
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			$objTemplate = new \BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### XING LIST ###';
			$objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            // Code für Versionen ab 3.0.0
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;
			return $objTemplate->parse();
		}

		$this->xing_category = deserialize($this->xing_categories, true);

		// Return if there are no categories
		if (!is_array($this->xing_category) || !is_numeric($this->xing_category[0]))
		{
			return '';
		}

		return parent::generate();
	}


	/**
	 * Generate module
	 */
	protected function compile()
	{
		$objXing = $this->Database->prepare("SELECT tl_xing.id AS id, xingprofil, xinglayout, xingtarget, title"
		                                  ." FROM tl_xing LEFT JOIN tl_xing_category ON (tl_xing_category.id=tl_xing.pid)"
		                                  ." WHERE pid IN(" . implode(',', $this->xing_category) . ")" . (!BE_USER_LOGGED_IN ? " AND published=?" : "")
		                                  ." ORDER BY title, sorting")
								  ->execute(1);
		if ($objXing->numRows < 1)
		{
			//mod_xing_empty
			$this->strTemplate = 'mod_xing_empty';
            $this->Template = new \FrontendTemplate($this->strTemplate);

            $strText = 'mod_xing_empty, numRows < 1';
            $logger = static::getContainer()->get('monolog.logger.contao');
            $logger->log(LogLevel::ERROR, $strText, array('contao' => new ContaoContext('XingList', TL_ERROR)));
			return;
		}

		$arrXing = array();
		$XingImage = new XingImage(); // classes/XingImage.php

		while ($objXing->next())
		{
		    $this->xing_images = $XingImage->getXingImageLink($objXing->xinglayout);

    		if (($this->xing_template != $this->strTemplate) && ($this->xing_template == 'mod_xing_list_company'))
    		{
    			$this->xing_images = preg_replace('/title="[^"]*"/', 'title="Company"', $this->xing_images);
    		}

			if ($GLOBALS['objPage']->outputFormat == 'html5')
			{
				$this->xing_images = \StringUtil::toHtml5($this->xing_images);
				$arrXing[] = array
				(
	                'xingprofil' => trim($objXing->xingprofil),
					'xinglayout' => $this->xing_images,
					'xingtarget' => ($objXing->xingtarget == '1') ? '' : ' target="_blank"'
				);
			}
			else
			{
				$arrXing[] = array
				(
	                'xingprofil' => trim($objXing->xingprofil),
					'xinglayout' => $this->xing_images,
					'xingtarget' => ($objXing->xingtarget == '1') ? LINK_BLUR : LINK_NEW_WINDOW
				);
			}
		} // while
		if (($this->xing_template != $this->strTemplate) && ($this->xing_template != ''))
		{
	        $this->strTemplate = $this->xing_template;
	        $this->Template = new \FrontendTemplate($this->strTemplate);
		}
		$this->Template->category = $objXing->title;
		$this->Template->xing = $arrXing;
	} // compile
} // class
