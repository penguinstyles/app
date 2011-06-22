<?php

/**
 * This special page for Semantic MediaWiki implements a customisable form for
 * executing queries outside of articles. Results and format options are generated by 
 * SMW_SpecialAsk.php. 
 *
 * Currently adapted from current contents of SMW_SpecialAsk.php
 * This page is currently under development as part of the Google Summer of 
 * Code 2011 Program. 
 *
 * @file SMW_SpecialQueryCreator.php
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 *
 * @author Markus Krötzsch
 * @author Yaron Koren
 * @author Sanyam Goyal
 * @author Jeroen De Dauw
 * 
 *
 */
class SMWQueryCreatorPage extends SpecialPage {

	//protected $m_querystring = '';
	protected $m_params = array();
	//protected $m_printouts = array();
	//protected $m_editquery = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct( 'QueryCreator' );
		smwfLoadExtensionMessages( 'SemanticMediaWiki' );
	}

	/**
	 * Main entrypoint for the special page.
	 *
	 * @param string $p
	 */
	public function execute( $p ) {
		global $wgOut, $wgRequest, $smwgQEnabled;

		$this->setHeaders();

		if ( !$smwgQEnabled ) {
			$wgOut->addHTML( '<br />' . wfMsg( 'smw_iq_disabled' ) );
		} else {
			$this->makeHTMLForm();
		}
		SMWOutputs::commitToOutputPage( $wgOut ); // make sure locally collected output data is pushed to the output!
	}
	/**
	 * Adds the input query form
	 */
	protected function makeHTMLForm(){
		global $wgOut, $smwgQSortingSupport, $smwgResultFormats, $smwgAutocompleteInSpecialAsk;
		$result= "";
		$spectitle = $this->getTitleFor( 'Ask' );
		$result .= '<form name="ask" action="' . $spectitle->escapeLocalURL() . '" method="get">' . "\n" .
			'<input type="hidden" name="title" value="' . $spectitle->getPrefixedText() . '"/>';
		
		$result .= wfMsg('smw_qc_query_help');
		// Main query and printouts.
		$result .= '<p><strong>' . wfMsg( 'smw_ask_queryhead' ) . "</strong></p>\n";
		$result .= '<p><textarea name="q" rows="6"></textarea></p>';
		//show|hide additional options and querying help
		$result .= '<span id="show_additional_options" style="display:inline"><a href="#addtional" rel="nofollow" onclick="' . 
			 "document.getElementById('additional_options').style.display='block';" . 
			 "document.getElementById('show_additional_options').style.display='none';" . 
			 "document.getElementById('hide_additional_options').style.display='inline';" . '">' .
			 wfMsg( 'smw_show_addnal_opts' ) . '</a></span>';
		$result .= '<span id="hide_additional_options" style="display:none"><a href="#" rel="nofollow" onclick="' . 
			 "document.getElementById('additional_options').style.display='none';" . 
			 "document.getElementById('hide_additional_options').style.display='none';" . 
			 "document.getElementById('show_additional_options').style.display='inline';" . '">' .
			 wfMsg( 'smw_hide_addnal_opts' ) . '</a></span>';
		$result .=' | <a href="' . htmlspecialchars( wfMsg( 'smw_ask_doculink' ) ) . '">' . wfMsg( 'smw_ask_help' ) . '</a>';
		//additional options
		$result .= '<div id="additional_options" style="display:none">';
		$result .= '<p><strong>' . wfMsg( 'smw_ask_printhead' ) . "</strong></p>\n" .
			'<span style="font-weight: normal;">' . wfMsg( 'smw_ask_printdesc' ) . '</span>' . "\n" .
			'<p><textarea id = "add_property" name="po" cols="20" rows="6"></textarea></p>' . "\n";
 
		// sorting inputs
		if ( $smwgQSortingSupport ) {
			$result .= $this->addSortingOptions($result); 
		}

		$printer = SMWQueryProcessor::getResultPrinter( 'broadtable', SMWQueryProcessor::SPECIAL_PAGE );
		$url = SpecialPage::getSafeTitleFor( 'Ask' )->getLocalURL( "showformatoptions=' + this.value + '" );
		//@TODO //$url .= '&showformatoptions=broadtable&params[title]=Special:Ask&params[offset]=0&params[limit]=20';
		$url .= '&params[title]=Special:Ask&params[offset]=0&params[limit]=20';

		$result .= "<br /><br />\n<p>" . wfMsg( 'smw_ask_format_as' ) . ' <input type="hidden" name="eq" value="yes"/>' . "\n" .
				'<select id="formatSelector" name="p[format]" onChange="JavaScript:updateOtherOptions(\'' . $url . '\')">' . "\n" .
				'	<option value="broadtable">' .
				$printer->getName() . ' (' . wfMsg( 'smw_ask_defaultformat' ) . ')</option>' . "\n";

		$formats = array();

		foreach ( array_keys( $smwgResultFormats ) as $format ) {
			// Special formats "count" and "debug" currently not supported.
			if ( $format != 'broadtable' && $format != 'count' && $format != 'debug' ) {
				$printer = SMWQueryProcessor::getResultPrinter( $format, SMWQueryProcessor::SPECIAL_PAGE );
				$formats[$format] = $printer->getName();
			}
		}

		natcasesort( $formats );

		foreach ( $formats as $format => $name ) {
			$result .= '	<option value="' . $format . '>' . $name . "</option>\n";
		}
		//add javascript for updating formating options by ajax
		$default_format_url = SpecialPage::getSafeTitleFor( 'Ask' )->getLocalURL( "showformatoptions=broadtable" );
		$default_format_url .= '&params[title]=Special:Ask&params[offset]=0&params[limit]=20';
		$javascript = <<<END
<script type="text/javascript">
function updateOtherOptions(strURL) {
	jQuery.ajax({ url: strURL, context: document.body, success: function(data){
		jQuery("#other_options").html(data);
	}});
}
jQuery(document).ready(updateOtherOptions("{$default_format_url}"));
</script>
END;

		$wgOut->addScript( $javascript );

		$result .= "</select></p>\n";
		$result .= '<fieldset><legend>' . wfMsg( 'smw_ask_otheroptions' ) . "</legend>\n";
		$result .= "<div id=\"other_options\"></div>";
		$result .= "</fieldset>\n";
		$result .= '</div>';
		$result .= '<br /><input type="submit" value="' . wfMsg( 'smw_ask_submit' ) . '"/>' .
			'<input type="hidden" name="eq" value="no"/>' .
			"\n</form>";

	$wgOut->addHTML($result);

	}

	/**
	 * Javascript and HTML code to enable sorting over columns.
	 *
	 * @return string
	 */

	protected function addSortingOptions(){
	global $wgRequest, $wgOut, $smwgJQueryIncluded;


	$result='';
		if ( ! array_key_exists( 'sort', $this->m_params ) || ! array_key_exists( 'order', $this->m_params ) ) {
			$orders = array(); // do not even show one sort input here
		} else {
			$sorts = explode( ',', $this->m_params['sort'] );
			$orders = explode( ',', $this->m_params['order'] );
			reset( $sorts );
		}

		foreach ( $orders as $i => $order ) {
			$result .=  "<div id=\"sort_div_$i\">" . wfMsg( 'smw_ask_sortby' ) . ' <input type="text" name="sort[' . $i . ']" value="' .
				    htmlspecialchars( $sorts[$i] ) . "\" size=\"35\"/>\n" . '<select name="order[' . $i . ']"><option ';
				if ( $order == 'ASC' ) $result .= 'selected="selected" ';
			$result .=  'value="ASC">' . wfMsg( 'smw_ask_ascorder' ) . '</option><option ';
				if ( $order == 'DESC' ) $result .= 'selected="selected" ';

			$result .=  'value="DESC">' . wfMsg( 'smw_ask_descorder' ) . "</option></select>\n";
			$result .= '[<a href="javascript:removeInstance(\'sort_div_' . $i . '\')">' . wfMsg( 'delete' ) . '</a>]' . "\n";
			$result .= "</div>\n";
		}

		$result .=  '<div id="sorting_starter" style="display: none">' . wfMsg( 'smw_ask_sortby' ) . ' <input type="text" name="sort_num" size="35" />' . "\n";
		$result .= ' <select name="order_num">' . "\n";
		$result .= '	<option value="ASC">' . wfMsg( 'smw_ask_ascorder' ) . "</option>\n";
		$result .= '	<option value="DESC">' . wfMsg( 'smw_ask_descorder' ) . "</option>\n</select>\n";
		$result .= "</div>\n";
		$result .= '<div id="sorting_main"></div>' . "\n";
		$result .= '<a href="javascript:addInstance(\'sorting_starter\', \'sorting_main\')">' . wfMsg( 'smw_add_sortcondition' ) . '</a>' . "\n";
	
	 


		$this->m_num_sort_values = 0;

		if  ( !array_key_exists( 'sort', $this->m_params ) ) {
			$sort_values = $wgRequest->getArray( 'sort' );
			if ( is_array( $sort_values ) ) {
				$this->m_params['sort'] = implode( ',', $sort_values );
				$this->m_num_sort_values = count( $sort_values );
			}
		}
	// Javascript code for handling adding and removing the "sort" inputs
		$delete_msg = wfMsg( 'delete' );

		
		$javascript_text = <<<END
<script type="text/javascript">
// code for handling adding and removing the "sort" inputs
var num_elements = {$this->m_num_sort_values};

function addInstance(starter_div_id, main_div_id) {
	var starter_div = document.getElementById(starter_div_id);
	var main_div = document.getElementById(main_div_id);

	//Create the new instance
	var new_div = starter_div.cloneNode(true);
	var div_id = 'sort_div_' + num_elements;
	new_div.className = 'multipleTemplate';
	new_div.id = div_id;
	new_div.style.display = 'block';

	var children = new_div.getElementsByTagName('*');
	var x;
	for (x = 0; x < children.length; x++) {
		if (children[x].name)
			children[x].name = children[x].name.replace(/_num/, '[' + num_elements + ']');
	}

	//Create 'delete' link
	var remove_button = document.createElement('span');
	remove_button.innerHTML = '[<a href="javascript:removeInstance(\'sort_div_' + num_elements + '\')">{$delete_msg}</a>]';
	new_div.appendChild(remove_button);

	//Add the new instance
	main_div.appendChild(new_div);
	num_elements++;
}

function removeInstance(div_id) {
	var olddiv = document.getElementById(div_id);
	var parent = olddiv.parentNode;
	parent.removeChild(olddiv);
}
</script>

END;

		$wgOut->addScript( $javascript_text );

		if ( !$smwgJQueryIncluded ) {
			$realFunction = array( 'OutputPage', 'includeJQuery' );
			if ( is_callable( $realFunction ) ) {
				$wgOut->includeJQuery();
			} else {
				$scripts[] = "$smwgScriptPath/libs/jquery-1.4.2.min.js";
			}

			$smwgJQueryIncluded = true;
		}

	return $result;
	}


	/**
	 * Returns a Validator style Parameter definition.
	 * SMW 1.5.x style definitions are converted.
	 *
	 * @since 1.6
	 *
	 * @param mixed $param
	 *
	 * @return Parameter
	 */
	protected function toValidatorParam( $param ) {
		static $typeMap = array(
			'int' => Parameter::TYPE_INTEGER
		);

		if ( !( $param instanceof Parameter ) ) {
			if ( !array_key_exists( 'type', $param ) ) {
				$param['type'] = 'string';
			}

			$paramClass = $param['type'] == 'enum-list' ? 'ListParameter' : 'Parameter';
			$paramType = array_key_exists( $param['type'], $typeMap ) ? $typeMap[$param['type']] : Parameter::TYPE_STRING;

			$parameter = new $paramClass( $param['name'], $paramType );

			if ( array_key_exists( 'description', $param ) ) {
				$parameter->setDescription( $param['description'] );
			}

			if ( array_key_exists( 'values', $param ) && is_array( $param['values'] ) ) {
				$parameter->addCriteria( new CriterionInArray( $param['values'] ) );
			}

			return $parameter;
		}
		else {
			return $param;
		}
	}



    /**
     * Compatibility method to get the skin; MW 1.18 introduces a getSkin method in SpecialPage.
     *
     * @since 1.6
     *
     * @return Skin
     */
    public function getSkin() {
	if ( method_exists( 'SpecialPage', 'getSkin' ) ) {
	    return parent::getSkin();
	}
	else {
	    global $wgUser;
	    return $wgUser->getSkin();
	}
    }

}

