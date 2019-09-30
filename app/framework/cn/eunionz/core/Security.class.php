<?php
declare(strict_types=1);
/**
 * Eunionz PHP Framework Security
 * Created by PhpStorm.
 * User: liulin (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:10
 */

namespace cn\eunionz\core;

defined('APP_IN') or exit('Access Denied');
class Security extends Kernel {

    // xss hash value
    private $_xss_hash = '';

    // xss filtering on-off
    private $_enable_xss = true;

    // standardize new line on-off
    private $_standardize_newlines = true;

    // List of never allowed strings
    private $_never_allowed_str = array(
        'document.cookie'	=> '[removed]',
        'document.write'	=> '[removed]',
        '.parentNode'		=> '[removed]',
        '.innerHTML'		=> '[removed]',
        'window.location'	=> '[removed]',
        '-moz-binding'		=> '[removed]',
        '<!--'				=> '&lt;!--',
        '-->'				=> '--&gt;',
        '<![CDATA['			=> '&lt;![CDATA[',
        '<comment>'			=> '&lt;comment&gt;'
    );

    // List of never allowed regex replacement
    private $_never_allowed_regex = array(
        'javascript\s*:',
        'expression\s*(\(|&\#40;)', // CSS and IE
        'vbscript\s*:', // IE, surprise!
        'Redirect\s+302',
        "([\"'])?data\s*:[^\\1]*?base64[^\\1]*?,[^\\1]*?\\1?"
    );

    public function __construct()
    {
        $this->_enable_xss = self::getConfig('app', 'APP_XSS_FILTERING');
        $this->_standardize_newlines = true;
    }



    // --------------------------------------------------------------------

    /**
     * Clean Input Data
     *
     * This is a helper function. It escapes data and
     * standardizes newline characters to \n
     *
     * @param	string $str
     *
     * @return	string
     */
    private function _clean_input_data($str)
    {
        if (is_array($str))
        {
            $new_array = array();
            foreach ($str as $key => $val)
            {
                $new_array[$this->_clean_input_keys($key)] = $this->_clean_input_data($val);
            }
            return $new_array;
        }

        /* We strip slashes if magic quotes is on to keep things consistent

           NOTE: In PHP 5.4 get_magic_quotes_gpc() will always return 0 and
             it will probably not exist in future versions at all.
        */
        if (get_magic_quotes_gpc())
        {
            $str = stripslashes($str);
        }

        // Clean UTF-8 if supported
        $str = $this->clean_string($str);
        // Remove control characters
        $str = $this->remove_invisible_characters($str);

        // Should we filter the input data?
        if ($this->_enable_xss === true)
        {
            $str = $this->xss_clean($str);
        }


        // Standardize newlines if needed
        if ($this->_standardize_newlines == true)
        {
            if (strpos($str, "\r") !== false)
            {
                $str = str_replace(array("\r\n", "\r", "\r\n\n"), PHP_EOL, $str);
            }
        }

        return $str;
    }

    // --------------------------------------------------------------------

    /**
     * Clean Keys
     *
     * @param	string $str
     *
     * @return	string
     */
    private function _clean_input_keys($str)
    {
        return $str;
//        if ( ! preg_match("/^[a-z0-9:_\/-]+$/i", $str))
//        {
////            exit('Commit illegal parameter.');
//            return false;
//        }
//
//        return $this->clean_string($str);
    }

    // --------------------------------------------------------------------

    /**
     * Clean UTF-8 strings
     *
     * Ensures strings are UTF-8
     *
     * @param	string $str
     *
     * @return	string
     */
    public function clean_string($str)
    {
        if ($this->is_ascii($str) === false)
        {
            $str = @iconv('UTF-8', 'UTF-8//IGNORE', $str);
        }

        return $str;
    }

    // --------------------------------------------------------------------

    /**
     * testing is ASCII
     *
     * Tests if a string is standard 7-bit ASCII or not
     *
     * @param	string $str
     *
     * @return	bool
     */
    public function is_ascii($str)
    {
        return (preg_match('/[^\x00-\x7F]/S', $str) == 0);
    }


    /**
     * remove invisible characters
     *
     * @param string $str
     * @param bool   $url_encoded
     *
     * @return mixed
     */
    function remove_invisible_characters($str, $url_encoded = true)
    {
        $non_displayables = array();

        // every control character except newline (dec 10)
        // carriage return (dec 13), and horizontal tab (dec 09)

        if ($url_encoded)
        {
            $non_displayables[] = '/%0[0-8bcef]/';	// url encoded 00-08, 11, 12, 14, 15
            $non_displayables[] = '/%1[0-9a-f]/';	// url encoded 16-31
        }

        $non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';	// 00-08, 11, 12, 14-31, 127

        do
        {
            $str = preg_replace($non_displayables, '', $str, -1, $count);
        }
        while ($count);

        return $str;
    }

    /**
     * XSS Clean
     *
     * Sanitizes data so that Cross Site Scripting Hacks can be
     * prevented.  This function does a fair amount of work but
     * it is extremely thorough, designed to prevent even the
     * most obscure XSS attempts.  Nothing is ever 100% foolproof,
     * of course, but I haven't been able to get anything passed
     * the filter.
     *
     * Note: This function should only be used to deal with data
     * upon submission.  It's not something that should
     * be used for general runtime processing.
     *
     * This function was based in part on some qrcode and ideas I
     * got from Bitflux: http://channel.bitflux.ch/wiki/XSS_Prevention
     *
     * To help develop this script I used this great list of
     * vulnerabilities along with a few other hacks I've
     * harvested from examining vulnerabilities in other programs:
     * http://ha.ckers.org/xss.html
     *
     * @param	mixed	string or array
     * @param 	bool
     * @return	string
     */
    public function xss_clean($str, $is_image = false)
    {
        /*
         * Is the string an array?
         *
         */
        if (is_array($str))
        {
            while (list($key) = each($str))
            {
                $str[$key] = $this->xss_clean($str[$key]);
            }

            return $str;
        }

        /*
         * Remove Invisible Characters
         */
        $str = $this->remove_invisible_characters($str);

        // Validate Entities in URLs
        $str = $this->_validate_entities($str);
        /*
         * URL Decode
         *
         * Just in case stuff like this is submitted:
         *
         * <a href="http://%77%77%77%2E%67%6F%6F%67%6C%65%2E%63%6F%6D">Google</a>
         *
         * Note: Use rawurldecode() so it does not remove plus signs
         *
         */
        $str = rawurldecode($str);

        /*
         * Convert character entities to ASCII
         *
         * This permits our tests below to work reliably.
         * We only convert entities that are within tags since
         * these are the ones that will pose security problems.
         *
         */
        $str = preg_replace_callback("/[a-z]+=([\'\"]).*?\\1/si", array($this, '_convert_attribute'), $str);

        $str = preg_replace_callback("/<\w+.*?(?=>|<|$)/si", array($this, '_decode_entity'), $str);

        /*
         * Remove Invisible Characters Again!
         */
        $str = $this->remove_invisible_characters($str);

        /*
         * Convert all tabs to spaces
         *
         * This prevents strings like this: ja	vascript
         * NOTE: we deal with spaces between characters later.
         * NOTE: preg_replace was found to be amazingly slow here on
         * large blocks of data, so we use str_replace.
         */

        if (strpos($str, "\t") !== false)
        {
            $str = str_replace("\t", ' ', $str);
        }

        /*
         * Capture converted string for later comparison
         */
        $converted_string = $str;

        // Remove Strings that are never allowed
        $str = $this->_do_never_allowed($str);

        /*
         * Makes PHP tags safe
         *
         * Note: XML tags are inadvertently replaced too:
         *
         * <?xml
         *
         * But it doesn't seem to pose a problem.
         */
        if ($is_image === true)
        {

            // Images have a tendency to have the PHP short opening and
            // closing tags every so often so we skip those and only
            // do the long opening tags.
            $str = preg_replace('/<\?(php)/i', "&lt;?\\1", $str);
        }
        else
        {
            $str = str_replace(array('<?', '?'.'>'),  array('&lt;?', '?&gt;'), $str);
        }

        /*
         * Compact any exploded words
         *
         * This corrects words like:  j a v a s c r i p t
         * These words are compacted back to their correct state.
         */
        $words = array(
            'javascript', 'expression', 'vbscript', 'script', 'base64',
            'applet', 'alert', 'document', 'write', 'cookie', 'window'
        );

        foreach ($words as $word)
        {
            $temp = '';

            for ($i = 0, $wordlen = strlen($word); $i < $wordlen; $i++)
            {
                $temp .= substr($word, $i, 1)."\s*";
            }

            // We only want to do this when it is followed by a non-word character
            // That way valid stuff like "dealer to" does not become "dealerto"
            $str = preg_replace_callback('#('.substr($temp, 0, -3).')(\W)#is', array($this, '_compact_exploded_words'), $str);
        }

        /*
         * Remove disallowed Javascript in links or img tags
         * We used to do some version comparisons and use of stripos for PHP5,
         * but it is dog slow compared to these simplified non-capturing
         * preg_match(), especially if the pattern exists in the string
         */
        do
        {
            $original = $str;

            if (preg_match("/<a/i", $str))
            {
                $str = preg_replace_callback("#<a\s+([^>]*?)(>|$)#si", array($this, '_js_link_removal'), $str);
            }

            if (preg_match("/<img/i", $str))
            {
                $str = preg_replace_callback("#<img\s+([^>]*?)(\s?/?>|$)#si", array($this, '_js_img_removal'), $str);
            }

            if (preg_match("/script/i", $str) OR preg_match("/xss/i", $str))
            {
                $str = preg_replace("#<(/*)(script|xss)(.*?)\>#si", '[removed]', $str);
            }

        }
        while($original != $str);

        unset($original);

        // Remove evil attributes such as style, onclick and xmlns
        $str = $this->_remove_evil_attributes($str, $is_image);

        /*
         * Sanitize naughty HTML elements
         *
         * If a tag containing any of the words in the list
         * below is found, the tag gets converted to entities.
         *
         * So this: <blink>
         * Becomes: &lt;blink&gt;
         */
        $naughty = 'alert|applet|audio|basefont|base|behavior|bgsound|blink|body|embed|expression|form|frameset|frame|head|html|ilayer|iframe|input|isindex|layer|link|meta|object|plaintext|style|script|textarea|title|video|xml|xss';
        $str = preg_replace_callback('#<(/*\s*)('.$naughty.')([^><]*)([><]*)#is', array($this, '_sanitize_naughty_html'), $str);

        /*
         * Sanitize naughty scripting elements
         *
         * Similar to above, only instead of looking for
         * tags it looks for PHP and JavaScript commands
         * that are disallowed.  Rather than removing the
         * qrcode, it simply converts the parenthesis to entities
         * rendering the qrcode un-executable.
         *
         * For example:	eval('some qrcode')
         * Becomes:		eval&#40;'some qrcode'&#41;
         */
        $str = preg_replace('#(alert|cmd|passthru|eval|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)\((.*?)\)#si', "\\1\\2&#40;\\3&#41;", $str);


        // Final clean up
        // This adds a bit of extra precaution in case
        // something got through the above filters
        $str = $this->_do_never_allowed($str);

        /*
         * Images are Handled in a Special Way
         * - Essentially, we want to know that after all of the character
         * conversion is done whether any unwanted, likely XSS, qrcode was found.
         * If not, we return true, as the image is clean.
         * However, if the string post-conversion does not matched the
         * string post-removal of XSS, then it fails, as there was unwanted XSS
         * qrcode found and removed/changed during processing.
         */

        if ($is_image === true)
        {
            return ($str == $converted_string) ? true: false;
        }
        return $str;
    }

    // --------------------------------------------------------------------

    /**
     * Random Hash for protecting URLs
     *
     * @return	string
     */
    public function xss_hash()
    {
        if ($this->_xss_hash == '')
        {
            mt_srand();
            $this->_xss_hash = md5(strval(time() + mt_rand(0, 1999999999)));
        }

        return $this->_xss_hash;
    }

    // --------------------------------------------------------------------

    /**
     * HTML Entities Decode
     *
     * This function is a replacement for html_entity_decode()
     *
     * The reason we are not using html_entity_decode() by itself is because
     * while it is not technically correct to leave out the semicolon
     * at the end of an entity most browsers will still interpret the entity
     * correctly.  html_entity_decode() does not convert entities without
     * semicolons, so we are left with our own little solution here. Bummer.
     *
     * @param	string
     * @param	string
     * @return	string
     */
    public function entity_decode($str, $charset='UTF-8')
    {
        if (stristr($str, '&') === false)
        {
            return $str;
        }

        $str = html_entity_decode($str, ENT_COMPAT, $charset);
        $str = preg_replace('~&#x(0*[0-9a-f]{2,5})~ei', 'chr(hexdec("\\1"))', $str);
        return preg_replace('~&#([0-9]{2,4})~e', 'chr(\\1)', $str);
    }

    // --------------------------------------------------------------------

    /**
     * Filename Security
     *
     * @param	string
     * @param 	bool
     * @return	string
     */
    public function sanitize_filename($str, $relative_path = false)
    {
        $bad = array(
            "../",
            "<!--",
            "-->",
            "<",
            ">",
            "'",
            '"',
            '&',
            '$',
            '#',
            '{',
            '}',
            '[',
            ']',
            '=',
            ';',
            '?',
            "%20",
            "%22",
            "%3c",		// <
            "%253c",	// <
            "%3e",		// >
            "%0e",		// >
            "%28",		// (
            "%29",		// )
            "%2528",	// (
            "%26",		// &
            "%24",		// $
            "%3f",		// ?
            "%3b",		// ;
            "%3d"		// =
        );

        if ( ! $relative_path)
        {
            $bad[] = './';
            $bad[] = '/';
        }

        $str = $this->remove_invisible_characters($str, false);

        return stripslashes(str_replace($bad, '', $str));
    }

    /**
     * Compact Exploded Words
     *
     * Callback function for xss_clean() to remove whitespace from
     * things like j a v a s c r i p t
     *
     * @param	type
     * @return	type
     */
    private function _compact_exploded_words($matches)
    {
        return preg_replace('/\s+/s', '', $matches[1]).$matches[2];
    }

    /**
     * Remove Evil HTML Attributes (like evenhandlers and style)
     *
     * It removes the evil attribute and either:
     * 	- Everything up until a space
     *		For example, everything between the pipes:
     *		<a |style=document.write('hello');alert('world');| class=link>
     * 	- Everything inside the quotes
     *		For example, everything between the pipes:
     *		<a |style="document.write('hello'); alert('world');"| class="link">
     *
     * @param string $str The string to check
     * @param boolean $is_image true if this is an image
     * @return string The string with the evil attributes removed
     */
    private function _remove_evil_attributes($str, $is_image)
    {
        // All javascript event handlers (e.g. onload, onclick, onmouseover), style, and xmlns
        //$evil_attributes = array('on\w*', 'style', 'xmlns', 'formaction');
        $evil_attributes = array('on\w*', 'xmlns', 'formaction');

        if ($is_image === true)
        {
            /*
             * Adobe Photoshop puts XML metadata into JFIF images,
             * including namespacing, so we have to allow this for images.
             */
            unset($evil_attributes[array_search('xmlns', $evil_attributes)]);
        }

        do {
            $count = 0;
            $attribs = array();

            // find occurrences of illegal attribute strings with quotes (042 and 047 are octal quotes)
            preg_match_all('/('.implode('|', $evil_attributes).')\s*=\s*(\042|\047)([^\\2]*?)(\\2)/is', $str, $matches, PREG_SET_ORDER);

            foreach ($matches as $attr)
            {
                $attribs[] = preg_quote($attr[0], '/');
            }

            // find occurrences of illegal attribute strings without quotes
            preg_match_all('/('.implode('|', $evil_attributes).')\s*=\s*([^\s>]*)/is', $str, $matches, PREG_SET_ORDER);

            foreach ($matches as $attr)
            {
                $attribs[] = preg_quote($attr[0], '/');
            }

            // replace illegal attribute strings that are inside an html tag
            if (count($attribs) > 0)
            {
                $str = preg_replace('/(<?)(\/?[^><]+?)([^A-Za-z<>\-])(.*?)('.implode('|', $attribs).')(.*?)([\s><]?)([><]*)/i', '$1$2 $4$6$7$8', $str, -1, $count);
            }

        } while ($count);

        return $str;
    }

    /**
     * Sanitize Naughty HTML
     *
     * Callback function for xss_clean() to remove naughty HTML elements
     *
     * @param	array
     * @return	string
     */
    private function _sanitize_naughty_html($matches)
    {
        // encode opening brace
        $str = '&lt;'.$matches[1].$matches[2].$matches[3];

        // encode captured opening or closing brace to prevent recursive vectors
        $str .= str_replace(array('>', '<'), array('&gt;', '&lt;'),
            $matches[4]);

        return $str;
    }

    /**
     * JS Link Removal
     *
     * Callback function for xss_clean() to sanitize links
     * This limits the PCRE backtracks, making it more performance friendly
     * and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in
     * PHP 5.2+ on link-heavy strings
     *
     * @param	array
     * @return	string
     */
    private function _js_link_removal($match)
    {
        return str_replace(
            $match[1],
            preg_replace(
                '#href=.*?(alert\(|alert&\#40;|javascript\:|livescript\:|mocha\:|charset\=|window\.|document\.|\.cookie|<script|<xss|data\s*:)#si',
                '',
                $this->_filter_attributes(str_replace(array('<', '>'), '', $match[1]))
            ),
            $match[0]
        );
    }

    /**
     * JS Image Removal
     *
     * Callback function for xss_clean() to sanitize image tags
     * This limits the PCRE backtracks, making it more performance friendly
     * and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in
     * PHP 5.2+ on image tag heavy strings
     *
     * @param	array
     * @return	string
     */
    private function _js_img_removal($match)
    {
        return str_replace(
            $match[1],
            preg_replace(
                '#src=.*?(alert\(|alert&\#40;|javascript\:|livescript\:|mocha\:|charset\=|window\.|document\.|\.cookie|<script|<xss|base64\s*,)#si',
                '',
                $this->_filter_attributes(str_replace(array('<', '>'), '', $match[1]))
            ),
            $match[0]
        );
    }

    /**
     * Attribute Conversion
     *
     * Used as a callback for XSS Clean
     *
     * @param	array
     * @return	string
     */
    private function _convert_attribute($match)
    {
        return str_replace(array('>', '<', '\\'), array('&gt;', '&lt;', '\\\\'), $match[0]);
    }

    /**
     * Filter Attributes
     *
     * Filters tag attributes for consistency and safety
     *
     * @param	string
     * @return	string
     */
    private function _filter_attributes($str)
    {
        $out = '';

        if (preg_match_all('#\s*[a-z\-]+\s*=\s*(\042|\047)([^\\1]*?)\\1#is', $str, $matches))
        {
            foreach ($matches[0] as $match)
            {
                $out .= preg_replace("#/\*.*?\*/#s", '', $match);
            }
        }

        return $out;
    }

    /**
     * HTML Entity Decode Callback
     *
     * Used as a callback for XSS Clean
     *
     * @param	array
     * @return	string
     */
    private function _decode_entity($match)
    {
        return $this->entity_decode($match[0], 'UTF-8');
    }

    /**
     * Validate URL entities
     *
     * Called by xss_clean()
     *
     * @param 	string
     * @return 	string
     */
    private function _validate_entities($str)
    {
        /*
         * Protect GET variables in URLs
         */

        // 901119URL5918AMP18930PROTECT8198

        $str = preg_replace('|\&([a-z\_0-9\-]+)\=([a-z\_0-9\-]+)|i', $this->xss_hash()."\\1=\\2", $str);

        /*
         * Validate standard character entities
         *
         * Add a semicolon if missing.  We do this to enable
         * the conversion of entities to ASCII later.
         *
         */
        $str = preg_replace('#(&\#?[0-9a-z]{2,})([\x00-\x20])*;?#i', "\\1;\\2", $str);

        /*
         * Validate UTF16 two byte encoding (x00)
         *
         * Just as above, adds a semicolon if missing.
         *
         */
        $str = preg_replace('#(&\#x?)([0-9A-F]+);?#i',"\\1\\2;",$str);

        /*
         * Un-Protect GET variables in URLs
         */
        $str = str_replace($this->xss_hash(), '&', $str);

        return $str;
    }

    /**
     * Do Never Allowed
     *
     * A utility function for xss_clean()
     *
     * @param 	string
     * @return 	string
     */
    private function _do_never_allowed($str)
    {
        $str = str_replace(array_keys($this->_never_allowed_str), $this->_never_allowed_str, $str);

        foreach ($this->_never_allowed_regex as $regex)
        {
            $str = preg_replace('#'.$regex.'#is', '[removed]', $str);
        }

        return $str;
    }



    /**
     * filter field expression
     *
     * @param 	string
     * @return 	string
     */
    public function filter_field_expression($str)
    {
        return preg_replace("/F\\{([a-zA-Z0-9._]+)\\}/", 'F｛$1｝', $str);
    }

    /**
     * global filtering
     *
     * global filtering input data
     */
    public function global_filtering()
    {
        //  filtering $_GET
        if (is_array(ctx()->getRequest()->get()) && ! empty(ctx()->getRequest()->get()))
        {
            foreach (ctx()->getRequest()->get() as $key => $val)
                ctx()->getRequest()->get($this->_clean_input_keys($key) ,  $this->_clean_input_data($val));
        }

        // filtering $_POST
        if (is_array(ctx()->getRequest()->post()) && ! empty(ctx()->getRequest()->post()))
        {
            foreach (ctx()->getRequest()->post() as $key => $val){
                ctx()->getRequest()->post($this->_clean_input_keys($key), $this->_clean_input_data($val));
            }
        }


        // filtering $_COOKIE
        if (is_array(ctx()->getRequest()->cookie()) && ! empty(ctx()->getRequest()->cookie()))
        {
            foreach (ctx()->getRequest()->cookie() as $key => $val)
                ctx()->getRequest()->cookie($this->_clean_input_keys($key), $this->_clean_input_data($val));
        }

        // filtering $_REQUEST
        if (is_array(ctx()->getRequest()->request()) && ! empty(ctx()->getRequest()->request()))
        {
            foreach (ctx()->getRequest()->request() as $key => $val){
                ctx()->getRequest()->request($this->_clean_input_keys($key), $this->_clean_input_data($val));
            }
        }
    }

    /**
     * global filtering field expression
     *
     * global filtering input data
     */
    public function global_filter_field_expr()
    {

        //  filtering $_GET
        if (is_array(ctx()->getRequest()->get()) && ! empty(ctx()->getRequest()->get()))
        {
            foreach (ctx()->getRequest()->get() as $key => $val){
                $key=$this->_clean_input_keys($key);
                if($key){
                    ctx()->getRequest()->get($key, $this->_clean_input_field_expr($val));
                }
            }

        }

        // filtering $_POST
        if (is_array(ctx()->getRequest()->post()) && ! empty(ctx()->getRequest()->post()))
        {
            foreach (ctx()->getRequest()->post() as $key => $val){
                $key=$this->_clean_input_keys($key);
                if($key){
                    ctx()->getRequest()->post($key, $this->_clean_input_field_expr($val));
                }
            }
        }

        // filtering $_COOKIE
        if (is_array(ctx()->getRequest()->cookie()) && ! empty(ctx()->getRequest()->cookie()))
        {
            foreach (ctx()->getRequest()->cookie() as $key => $val){
                $key=$this->_clean_input_keys($key);
                if($key){
                    ctx()->getRequest()->cookie($key, $this->_clean_input_field_expr($val));
                }

            }
        }


        // filtering $_REQUEST
        if (is_array(ctx()->getRequest()->request()) && ! empty(ctx()->getRequest()->request()))
        {
            foreach (ctx()->getRequest()->request() as $key => $val){
                $key=$this->_clean_input_keys($key);
                if($key){
                    ctx()->getRequest()->request($key, $this->_clean_input_field_expr($val));
                }
            }
        }

    }



    /**
     * Clean Input field expression Data
     *
     * This is a helper function. It escapes data and
     * standardizes newline characters to \n
     *
     * @param	string $str
     *
     * @return	string
     */
    private function _clean_input_field_expr($str)
    {
        if (is_array($str))
        {
            $new_array = array();
            foreach ($str as $key => $val)
            {
                $new_array[$key] = $this->_clean_input_field_expr($val);
            }
            return $new_array;
        }
        return $this->filter_field_expression($str);
    }


    public function remove_xss($val) {
        // remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are allowed
        // this prevents some character re-spacing such as <java\0script>
        // note that you have to handle splits with \n, \r, and \t later since they *are* allowed in some inputs
        $val = preg_replace('/([\x00-\x08,\x0b-\x0c,\x0e-\x19])/', '', $val);

        // straight replacements, the user should never need these since they're normal characters
        // this prevents like <IMG SRC=@avascript:alert('XSS')>
        $search = 'abcdefghijklmnopqrstuvwxyz';
        $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $search .= '1234567890!@#$%^&*()';
        $search .= '~`";:?+/={}[]-_|\'\\';
        for ($i = 0; $i < strlen($search); $i++) {
            // ;? matches the ;, which is optional
            // 0{0,7} matches any padded zeros, which are optional and go up to 8 chars

            // @ @ search for the hex values
            $val = preg_replace('/(&#[xX]0{0,8}'.dechex(ord($search[$i])).';?)/i', $search[$i], $val); // with a ;
            // @ @ 0{0,7} matches '0' zero to seven times
            $val = preg_replace('/(&#0{0,8}'.ord($search[$i]).';?)/', $search[$i], $val); // with a ;
        }

        // now the only remaining whitespace attacks are \t, \n, and \r
        $ra1 = array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base');
        $ra2 = array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
        $ra = array_merge($ra1, $ra2);

        $found = true; // keep replacing as long as the previous round replaced something
        while ($found == true) {
            $val_before = $val;
            for ($i = 0; $i < sizeof($ra); $i++) {
                $pattern = '/';
                for ($j = 0; $j < strlen($ra[$i]); $j++) {
                    if ($j > 0) {
                        $pattern .= '(';
                        $pattern .= '(&#[xX]0{0,8}([9ab]);)';
                        $pattern .= '|';
                        $pattern .= '|(&#0{0,8}([9|10|13]);)';
                        $pattern .= ')*';
                    }
                    $pattern .= $ra[$i][$j];
                }
                $pattern .= '/i';
                $replacement = substr($ra[$i], 0, 2).'<x>'.substr($ra[$i], 2); // add in <> to nerf the tag
                $val = preg_replace($pattern, $replacement, $val); // filter out the hex tags
                if ($val_before == $val) {
                    // no replacements were made, so exit the loop
                    $found = false;
                }
            }
        }
        return $val;
    }
}
