<?php

// Emulates the PHP Locale class, for those sites that do not have the Intl extension installed.
// The class will only be found by the autoloader if the global Locale class doesn't exist.
class Locale
{
  const LANG_TAG               = 'language';
  const EXTLANG_TAG            = 'extlang';
  const SCRIPT_TAG             = 'script';
  const REGION_TAG             = 'region';
  const VARIANT_TAG            = 'variant';
  const GRANDFATHERED_LANG_TAG = 'grandfathered';
  const PRIVATE_TAG            = 'private';
  
  // Searches the items in the array $langtag for the best match to the language range
  // specified in $locale according to RFC 4647's lookup algorithm.   The langtags and
  // locale can have subtags separated by '-' or '_' and the search is case insensitive.
  // Charsets (eg '.UTF-8') are stripped off $locale
  //
  // Returns the best match, or else an empty string if no match
  public static function lookup($langtag, $locale, $canonicalize = FALSE)
  {
    if (!empty($canonicalize))
    {
      throw new Exception('MRBS: the MRBS version of Locale::lookup() does not yet support $canonicalize = TRUE');
    }
    
    if (func_num_args() > 3)
    {
      throw new Exception('MRBS: optional fourth parameter to Locale::lookup() not yet supported');
    }
    
    // Get the langtags and locale in the same format, ie separated by '-' and
    // all lower case
    $standard_langtags = self::standardise($langtag);
    // Strip off any charset (eg '.UTF-8');
    $locale = preg_replace('/\..*$/', '', $locale);
    $standard_locale = self::standardise($locale);
    
    // Look for a match.   If there isn't one remove the last subtag from the end
    // of the locale and try again.
    while (FALSE === ($index = array_search($standard_locale, $standard_langtags)))
    {
      if (FALSE === ($pos = strrpos($standard_locale, '-')))
      {
        return '';
      }
      $standard_locale = substr($standard_locale, 0, $pos);
    }
    
    return $langtag[$index];  // Return the match in its original format
  }
  
  
    // Returns a key-value array of locale ID subtag elements.
  // Parses a language tag according to BCP 47 
  // See http://tools.ietf.org/html/bcp47
  public static function parseLocale($locale)
  {
    static $regex = array('extlang' => '/^[[:alpha:]]{3}$/',  // 3ALPHA
                          'script'  => '/^[[:alpha:]]{4}$/',  // 4ALPHA
                          'region'  => '/^[[:alpha:]]{2}$|^[[:digit:]]{3}$/', // 2ALPHA or 3DIGIT
                          'variant' => '/^[[:alnum:]]{5,8}$|^[[:digit:]][[:alnum:]]{3}$/');  // 5*8alphanum or (DIGIT 3alphanum)
                          
    static $grandfathered = array('en-GB-oed',      // Irregular
                                  'i-ami',
                                  'i-bnn',
                                  'i-default',
                                  'i-enochian',
                                  'i-hak',
                                  'i-klingon',
                                  'i-lux',
                                  'i-mingo',
                                  'i-navajo',
                                  'i-pwn',
                                  'i-tao',
                                  'i-tay',
                                  'i-tsu',
                                  'sgn-BE-FR',
                                  'sgn-BE-NL',
                                  'sgn-CH-DE',
                                  'art-lojban',     // Regular
                                  'cel-gaulish',
                                  'no-bok',
                                  'no-nyn',
                                  'zh-guoyu',
                                  'zh-hakka',
                                  'zh-min',
                                  'zh-min-nan',
                                  'zh-xiang');
                                  
    // First check for a grandfathered tag
    if (isset($locale) && in_array($locale, $grandfathered))
    {
      return array(self::GRANDFATHERED_LANG_TAG => $locale);
    }
    
    // Otherwise parse the subtags
    $result = array();
    
    if (isset($locale))
    {
      $subtags = preg_split('/[-_]/', $locale);
    }
    else
    {
      $subtags = array();
    }
    
    while (NULL !== ($subtag = array_shift($subtags)))
    {
      // Tags are case insensitive, so convert to lowercase before processing and then
      // later convert as necessary according to convention
      $subtag = strtolower($subtag);
      
      if ($subtag == 'x')
      {
        // If the subtag is an 'x' then everything else is a private subtag,
        // even if it occurs as the first subtag:
        // "The single-character subtag 'x' as the primary subtag indicates
        // that the language tag consists solely of subtags whose meaning is
        // defined by private agreement"
        $i = 0;
        while (NULL !== ($subtag = array_shift($subtags)))
        {
          $result[self::PRIVATE_TAG . $i] = $subtag;
          $i++;
        }
      }
      
      // The primary language subtag is the first subtag in a language tag and
      // cannot be omitted, with two exceptions:
      //
      //  o  The single-character subtag 'x' as the primary subtag ...
      //  o  The single-character subtag 'i' is used by some grandfathered tags ...
      elseif (!isset($result[self::LANG_TAG])) 
      {
        // [ISO639-1] recommends that language codes be written in lowercase ('mn' Mongolian).
        // As the subtag will already be lowercase there's no need to do anything else
        $result[self::LANG_TAG] = $subtag;
        // Check if the next subtag looks like a language extension
        if (count($subtags) > 0)
        {
          if (preg_match($regex['extlang'], $subtags[0]))
          {
            $result[self::EXTLANG_TAG] = strtolower(array_shift($subtags));
          }
        }
      }
      
      // Script
      elseif (preg_match($regex['script'], $subtag))
      {
        // [ISO15924] recommends that script codes use lowercase with the
        // initial letter capitalized ('Cyrl' Cyrillic).
        $result[self::SCRIPT_TAG] = ucfirst($subtag);
      }
      
      // Region
      elseif (preg_match($regex['region'], $subtag))
      {
        // [ISO3166-1] recommends that country codes be capitalized ('MN'
        // Mongolia).
        $result[self::REGION_TAG] = strtoupper($subtag);
      }
      
      // Variants
      elseif (preg_match($regex['variant'], $subtag))
      {
        $i = 0;
        do
        {
          // If the subtag doesn't look like a variant then we've got them all
          // and gone one subtag too far, so put it back
          if (!preg_match($regex['variant'], $subtag))
          {
            array_unshift($subtags, $subtag);
            break;
          }
          $result[self::VARIANT_TAG . $i] = $subtag;
        }
        while (NULL !== ($subtag = array_shift($subtags)));
      }
      
      else
      {
        trigger_error("parseLocale: could not parse subtag '$subtag'", E_USER_NOTICE);
      }
    }
    
    return $result;
  }
  
  
  // Converts $langtag, which can be a string or an array, into a standard form with
  // subtags all in lower case and separated by '-';
  private static function standardise($langtag)
  {
    $glue = ',';
    $result = (is_array($langtag)) ? implode($glue, $langtag) : $langtag;
    $result = MRBS\utf8_strtolower(str_replace('_', '-', $result));
    return (is_array($langtag)) ? explode($glue, $result) : $result;
  }
}