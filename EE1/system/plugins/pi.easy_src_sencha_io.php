<?php
/*
=====================================================
 Easy src.sencha.io - by Easy! Designs, LLC
-----------------------------------------------------
 http://easy-designs.net/
=====================================================
 This extension was created by Aaron Gustafson
 - aaron@easy-designs.net
 This work is licensed under the MIT License.
=====================================================
 File: pi.easy_src_sencha_io.php
-----------------------------------------------------
 Purpose: Converts img elements found within the 
 markup to Responsive images passed through the 
 src.sencha.io web service
=====================================================
*/

$plugin_info = array(
	'pi_name'			=> 'Easy src.sencha.io',
	'pi_version'		=> '1.0',
	'pi_author'			=> 'Aaron Gustafson',
	'pi_author_url'		=> 'http://easy-designs.net/',
	'pi_description'	=> 'Converts img elements found within the markup to Responsive images passed through the src.sencha.io web service',
	'pi_usage'			=> Easy_src_sencha_io::usage()
);

class Easy_src_sencha_io {

	var $return_data;
	var $img		= '<img src="http://src.sencha.io/{params}{img_src}"{attributes}/>';
	var $xhtml		= TRUE;
	var $base_url	= 'http://src.sencha.io/{params}{img_src}';
	var $max_width	= 'x100/';

	/**
	 * Easy_src_sencha_io constructor
	 * sets any overrides and triggers the processing
	 * 
	 * @param str $str - the content to be parsed
	 */
	function Easy_src_sencha_io ( $str = '' )
	{

		# get any tag overrides
		global $TMPL;
		if ( $temp = $TMPL->fetch_param('xhtml') )
		{
			$this->xhtml = ( $temp == 'n' ? FALSE : TRUE );
		}
		if ( $temp = $TMPL->fetch_param('max_width') )
		{
			$this->max_width = $temp . '/';	
			if ( strpos( $this->max_width, '%' ) != NULL )
			{
				$this->max_width = preg_replace( '/(\\d+)%/', 'x$1', $this->max_width );
			}
		}
		
		# check to make sure the service is up using cURL
		if ( @is_array( @get_headers( 'http://src.sencha.io/http://blog.easy-designs.net/img/easy-blog.png' ) ) )
		{
			$service_available = TRUE;
		}
		else
		{	
			$service_available = FALSE;
		}
		
		# Fetch string
		if ( empty( $str ) ) $str = $TMPL->tagdata;

		# return the processed string
		$this->return_data = ( $service_available && ! empty( $str ) ? $this->process( $str ) : $str );

	} # end Easy_src_sencha_io constructor
  
	/**
	 * Easy_src_sencha_io::process()
	 * processes the supplied content based on the configuration
	 * 
	 * @param str $str - the content to be parsed
	 */
	function process( $str )
	{
		global $FNS;

		# trim
		$str = trim( $str );

		$lookup = '/(<img([^>]*)\/?>)/';
		if ( preg_match_all( $lookup, $str, $found, PREG_SET_ORDER ) )
		{
			# loop the matches
			foreach ( $found as $instance )
			{
				$o_img = $instance[1];
				# get the attributes
				$attributes	= array();
				foreach ( explode( ' ', trim( $instance[2] ) ) as $attr )
				{
					if ( $attr != '/' )
					{
						preg_match_all( '/([^=]*)=([\'"])(.*)\\2$/', $attr, $matches, PREG_SET_ORDER );
						if ( $matches[0][1] == 'src')
						{
							$src = $matches[0][3];
						}
						else
						{
							$attributes[$matches[0][1]] = $matches[0][1] . '="' . $matches[0][3] . '"';
						}
					}
				}
				# drop any width & height attributes
				unset( $attributes['width'], $attributes['height'] );
				# check the src
				$protocol = ( !empty( $_SERVER['HTTPS'] ) ? 'https' : 'http' );
				if ( substr( $src, 0, 1 ) == '/' )
				{
					# same protocol
					if ( substr( $src, 0, 2 ) == '//' )
					{
						$src = $protocol . ':' . $src;
					}
					else
					{
						$src = $protocol . "://{$_SERVER['HTTP_HOST']}" . $src;
					}
				}
				else if ( substr( $src, 0, 4 ) != 'http' )
				{
					$src = $protocol . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}" . $src;
				}
				# build the new image
				$swap = array(
					'attributes'	=> implode( ' ', $attributes ),
					'params'		=> $this->max_width,
					'img_src'		=> $src
				);
				$n_img = $FNS->var_swap( $this->img, $swap );
				if ( ! $this->xhtml )
				{
					$n_img = str_replace( '/>', '>', $n_img );
				}
				$str = str_replace( $o_img, $n_img, $str );
			} # end foreach instance
		} # end if match
		return $str;
	} # end Easy_src_sencha_io::process()

	/**
	 * Easy_src_sencha_io::usage()
	 * Describes how the plugin is used
	 */
	function usage()
	{
		ob_start(); ?>
If you want responsive images without the headaches, you can use this plugin to implement Sencha’s src.sencha.io image service.

{exp:easy_src_sencha_io}
	{body}
{/exp:easy_src_sencha_io}

when the plugin encounters

<img src="foo.png" alt=""/>

it will remake that as

<img src="http://src.sencha.io/x100/http://your.domain.com/path/to/foo.png" alt=""/>

Providing it with additional params allows you to customize the image size:

{exp:easy_src_sencha_io max_width="50%"}
	{body}
{/exp:easy_src_sencha_io}

will generate the appropriate URL:

<img src="http://src.sencha.io/x50/http://your.domain.com/path/to/foo.png" alt=""/>

For more options, see http://www.sencha.com/learn/how-to-use-src-sencha-io/. Note: I’ve decided not to implement height adjustment, so just use a width value.

You can also turn off the default XHTML presentation (to drop the trailing slash) if you are an HTML5 fan:

{exp:easy_src_sencha_io xhtml="n"}
	{body}
{/exp:easy_src_sencha_io}

<?php
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	} # end Easy_src_sencha_io::usage()

} # end Easy_src_sencha_io


