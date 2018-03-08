<?php

namespace MRBS;

class System
{
  // A map is needed to convert from the HTTP language specifier to a
  // locale specifier for Windows
  //
  // The ordering of this array is important as it is also used to map in the
  // reverse direction, ie to convert a Windows style locale into an xx-yy style
  // locale by finding the first occurrence of a value and then using the
  // corresponding key.
  //
  // These locale TLAs found at:
  // https://www.microsoft.com/resources/msdn/goglobal/default.mspx
  private static $lang_map_windows = array
    (
      'bg' => 'bgr',
      'bg-bg' => 'bgr',
      'ca' => 'cat',
      'ca-es' => 'cat',
      'cs' => 'csy',
      'cs-cz' => 'csy',
      'da' => 'dan',
      'da-dk' => 'dan',
      'de' => 'deu',
      'de-at' => 'dea',
      'de-ch' => 'des',
      'de-de' => 'deu',
      'de-li' => 'dec',
      'de-lu' => 'del',
      'el' => 'ell',
      'el-gr' => 'ell',
      'en' => 'enu',
      'en-au' => 'ena',
      'en-bz' => 'enl',
      'en-ca' => 'enc',
      'en-cb' => 'enb',
      'en-gb' => 'eng',
      'en-ie' => 'eni',
      'en-in' => 'enn',
      'en-jm' => 'enj',
      'en-my' => 'enm',
      'en-nz' => 'enz',
      'en-ph' => 'enp',
      'en-tt' => 'ent',
      'en-us' => 'enu',
      'en-za' => 'ens',
      'en-zw' => 'enw',
      'es' => 'esp',
      'es-ar' => 'ess',
      'es-bo' => 'esb',
      'es-cl' => 'esl',
      'es-co' => 'eso',
      'es-cr' => 'esc',
      'es-do' => 'esd',
      'es-ec' => 'esf',
      'es-es' => 'esn',
      'es-gt' => 'esg',
      'es-hn' => 'esh',
      'es-mx' => 'esm',
      'es-ni' => 'esi',
      'es-pa' => 'esa',
      'es-pe' => 'esr',
      'es-py' => 'esz',
      'es-sv' => 'ese',
      'es-us' => 'est',
      'es-uy' => 'esy',
      'es-ve' => 'esv',
      'et' => 'eti',
      'et-ee' => 'eti',
      'eu' => 'euq',
      'eu-es' => 'euq',
      'fi' => 'fin',
      'fi-fi' => 'fin',
      'fr' => 'fra',
      'fr-be' => 'frb',
      'fr-ca' => 'frc',
      'fr-ch' => 'frs',
      'fr-fr' => 'fra',
      'fr-lu' => 'frl',
      'fr-mc' => 'frm',
      'he' => 'heb',
      'he-il' => 'heb',
      'hr' => 'hrv',
      'hr-hr' => 'hrv',
      'hu' => 'hun',
      'hu-hu' => 'hun',
      'id' => 'ind',
      'id-id' => 'ind',
      'it' => 'ita',
      'it-ch' => 'its',
      'it-it' => 'ita',
      'ja' => 'jpn',
      'ja-jp' => 'jpn',
      'ko' => 'kor',
      'ko-kr' => 'kor',
      'nb' => 'nor',
      'nb-no' => 'nor',
      'nl' => 'nld',
      'nl-be' => 'nlb',
      'nl-nl' => 'nld',
      'nn' => 'non',
      'nn-no' => 'non',
      'no' => 'nor',
      'no-no' => 'nor',
      'pl' => 'plk',
      'pl-pl' => 'plk',
      'pt' => 'ptb',
      'pt-br' => 'ptb',
      'pt-pt' => 'ptg',
      'ro' => 'rom',
      'ro-ro' => 'rom',
      'ru' => 'rus',
      'ru-ru' => 'rus',
      'sk' => 'sky',
      'sk-sk' => 'sky',
      'sl' => 'slv',
      'sl-si' => 'slv',
      'sr' => 'srl',
      'sr-rs-latin' => 'srl',
      'sr-hr' => 'srb',
      'sv' => 'sve',
      'sv-fi' => 'svf',
      'sv-se' => 'sve',
      'th' => 'tha',
      'th-th' => 'tha',
      'tr' => 'trk',
      'tr-tr' => 'trk',
      'zh' => 'chs',
      'zh-cn' => 'chs',
      'zh-hk' => 'zhh',
      'zh-mo' => 'zhm',
      'zh-sg' => 'zhi',
      'zh-tw' => 'cht'
    );
    
  // This maps a Windows locale to the charset it uses, which are
  // all Windows code pages
  //
  // The code pages used by these locales found at: 
  // http://msdn.microsoft.com/en-us/goglobal/bb896001.aspx
  private static $winlocale_codepage_map = array
    (
      'afk' => 'CP1252',
      'ara' => 'CP1256',
      'arb' => 'CP1256',
      'are' => 'CP1256',
      'arg' => 'CP1256',
      'arh' => 'CP1256',
      'ari' => 'CP1256',
      'arj' => 'CP1256',
      'ark' => 'CP1256',
      'arl' => 'CP1256',
      'arm' => 'CP1256',
      'aro' => 'CP1256',
      'arq' => 'CP1256',
      'ars' => 'CP1256',
      'art' => 'CP1256',
      'ary' => 'CP1256',
      'aze' => 'CP1254',
      'bas' => 'CP1251',
      'bel' => 'CP1251',
      'bgr' => 'CP1251',
      'bre' => 'CP1252',
      'bsb' => 'CP1250',
      'bsc' => 'CP1251',
      'cat' => 'CP1252',
      'chs' => 'CP936',
      'cht' => 'CP950',
      'cos' => 'CP1252',
      'csy' => 'CP1250',
      'cym' => 'CP1252',
      'dan' => 'CP1252',
      'dea' => 'CP1252',
      'dec' => 'CP1252',
      'del' => 'CP1252',
      'des' => 'CP1252',
      'deu' => 'CP1252',
      'dsb' => 'CP1252',
      'ell' => 'CP1253',
      'ena' => 'CP1252',
      'enb' => 'CP1252',
      'enc' => 'CP1252',
      'ene' => 'CP1252',
      'eng' => 'CP1252',
      'eni' => 'CP1252',
      'enj' => 'CP1252',
      'enl' => 'CP1252',
      'enm' => 'CP1252',
      'enn' => 'CP1252',
      'enp' => 'CP1252',
      'ens' => 'CP1252',
      'ent' => 'CP1252',
      'enu' => 'CP1252',
      'enw' => 'CP1252',
      'enz' => 'CP1252',
      'esa' => 'CP1252',
      'esb' => 'CP1252',
      'esc' => 'CP1252',
      'esd' => 'CP1252',
      'ese' => 'CP1252',
      'esf' => 'CP1252',
      'esg' => 'CP1252',
      'esh' => 'CP1252',
      'esi' => 'CP1252',
      'esl' => 'CP1252',
      'esm' => 'CP1252',
      'esn' => 'CP1252',
      'eso' => 'CP1252',
      'esp' => 'CP1252',
      'esr' => 'CP1252',
      'ess' => 'CP1252',
      'est' => 'CP1252',
      'esu' => 'CP1252',
      'esv' => 'CP1252',
      'esy' => 'CP1252',
      'esz' => 'CP1252',
      'euq' => 'CP1252',
      'eti' => 'CP1257',
      'far' => 'CP1256',
      'fin' => 'CP1252',
      'fos' => 'CP1252',
      'fpo' => 'CP1252',
      'fra' => 'CP1252',
      'frb' => 'CP1252',
      'frc' => 'CP1252',
      'frl' => 'CP1252',
      'frm' => 'CP1252',
      'frs' => 'CP1252',
      'fyn' => 'CP1252',
      'glc' => 'CP1252',
      'gsw' => 'CP1252',
      'hau' => 'CP1252',
      'heb' => 'CP1255',
      'hrb' => 'CP1250',
      'hrv' => 'CP1250',
      'hsb' => 'CP1252',
      'hun' => 'CP1250',
      'ibo' => 'CP1252',
      'ind' => 'CP1252',
      'ire' => 'CP1252',
      'isl' => 'CP1252',
      'ita' => 'CP1252',
      'its' => 'CP1252',
      'iuk' => 'CP1252',
      'jpn' => 'CP932',
      'kal' => 'CP1252',
      'kin' => 'CP1252',
      'kkz' => 'CP1251',
      'kor' => 'CP949',
      'kyr' => 'CP1251',
      'lbx' => 'CP1252',
      'lth' => 'CP1257',
      'lvi' => 'CP1257',
      'mki' => 'CP1251',
      'mon' => 'CP1251',
      'mpd' => 'CP1252',
      'msb' => 'CP1252',
      'msl' => 'CP1252',
      'mwk' => 'CP1252',
      'nlb' => 'CP1252',
      'nld' => 'CP1252',
      'non' => 'CP1252',
      'nor' => 'CP1252',
      'nso' => 'CP1252',
      'oci' => 'CP1252',
      'plk' => 'CP1250',
      'prs' => 'CP1256',
      'ptb' => 'CP1252',
      'ptg' => 'CP1252',
      'qub' => 'CP1252',
      'que' => 'CP1252',
      'qup' => 'CP1252',
      'qut' => 'CP1252',
      'rmc' => 'CP1252',
      'rom' => 'CP1250',
      'rus' => 'CP1251',
      'sah' => 'CP1252',
      'sky' => 'CP1250',
      'slv' => 'CP1250',
      'sma' => 'CP1252',
      'smb' => 'CP1252',
      'sme' => 'CP1252',
      'smf' => 'CP1252',
      'smg' => 'CP1252',
      'smj' => 'CP1252',
      'smk' => 'CP1252',
      'smn' => 'CP1252',
      'sms' => 'CP1252',
      'sqi' => 'CP1250',
      'srb' => 'CP1251',
      'srl' => 'CP1250',
      'srn' => 'CP1251',
      'srs' => 'CP1250',
      'sve' => 'CP1252',
      'svf' => 'CP1252',
      'swk' => 'CP1252',
      'taj' => 'CP1251',
      'tha' => 'CP874',
      'trk' => 'CP1254',
      'tsn' => 'CP1252',
      'ttt' => 'CP1251',
      'tuk' => 'CP1250',
      'tzm' => 'CP1252',
      'uig' => 'CP1256',
      'ukr' => 'CP1251',
      'urb' => 'CP1256',
      'uzb' => 'CP1254',
      'vit' => 'CP1258',
      'wol' => 'CP1252',
      'xho' => 'CP1252',
      'yor' => 'CP1252',
      'zhh' => 'CP950',
      'zhi' => 'CP936',
      'zhm' => 'CP950',
      'zul' => 'CP1252',
    );
    
  // These are special cases, generally we can convert from the HTTP
  // language specifier to a locale specifier without a map
  private static $lang_map_unix = array
    (
      'ca' => 'ca_ES',
      'cs' => 'cs_CZ',
      'da' => 'da_DK',
      'el' => 'el_GR',
      'en' => 'en_GB',
      'et' => 'et_EE',
      'eu' => 'eu_ES',
      'ja' => 'ja_JP',
      'ko' => 'ko_KR',
      'nb' => 'nb_NO',
      'nn' => 'nn_NO',
      'sh' => 'sr_RS',
      'sl' => 'sl_SI',
      'sr-rs-latin' => 'sr_RS',
      'sv' => 'sv_SE',
      'zh' => 'zh_CN',
    );
  
  // A map to add extra locale specifiers onto the end of a locale specifier
  // *after* the UTF-8 specifier is added. Keys must match with $lang_map_unix
  // above
  private static $lang_map_unix_postfix = array
    (
      'sr-rs-latin' => '@latin',
    );
    
  // IBM AIX locale to code set table
  // See http://publibn.boulder.ibm.com/doc_link/en_US/a_doc_lib/libs/basetrf2/setlocale.htm
  private static $aixlocale_codepage_map = array
    (
      'Ar_AA' => 'IBM-1046',
      'ar_AA' => 'ISO8859-6',
      'bg_BG' => 'ISO8856-5',
      'cs_CZ' => 'ISO8859-2',
      'Da_DK' => 'IBM-850',
      'da_DK' => 'ISO8859-1',
      'De_CH' => 'IBM-850',
      'de_CH' => 'ISO8859-1',
      'De_DE' => 'IBM-850',
      'de_DE' => 'ISO8859-1',
      'el_GR' => 'ISO8859-7',
      'En_GB' => 'IBM-850',
      'en_GB' => 'ISO8859-1',
      'En_US' => 'IBM-850',
      'en_US' => 'ISO8859-1',
      'Es_ES' => 'IBM-850',
      'es_ES' => 'ISO8859-1',
      'Fi_FI' => 'IBM-850',
      'fi_FI' => 'ISO8859-1',
      'Fr_BE' => 'IBM-850',
      'fr_BE' => 'ISO8859-1',
      'Fr_CA' => 'IBM-850',
      'fr_CA' => 'ISO8859-1',
      'Fr_FR' => 'IBM-850',
      'fr_FR' => 'ISO8859-1 ',
      'Fr_CH' => 'IBM-850',
      'fr_CH' => 'ISO8859-1',
      'hr_HR' => 'ISO8859-2',
      'hu_HU' => 'ISO8859-2',
      'Is_IS' => 'IBM-850',
      'is_IS' => 'ISO8859-1',
      'It_IT' => 'IBM-850',
      'it_IT' => 'ISO8859-1',
      'Iw_IL' => 'IBM-856',
      'iw_IL' => 'ISO8859-8',
      'Ja_JP' => 'IBM-943',
      'ko_KR' => 'IBM-eucKR',
      'mk_MK' => 'ISO8859-5',
      'Nl_BE' => 'IBM-850',
      'nl_BE' => 'ISO8859-1',
      'Nl_NL' => 'IBM-850',
      'nl_NL' => 'ISO8859-1',
      'No_NO' => 'IBM-850',
      'no_NO' => 'ISO8859-1',
      'pl_PL' => 'ISO8859-2',
      'Pt_PT' => 'IBM-850',
      'pt_PT' => 'ISO8859-1',
      'ro_RO' => 'ISO8859-2',
      'ru_RU' => 'ISO8859-5',
      'sh_SP' => 'ISO8859-2',
      'sl_SI' => 'ISO8859-2',
      'sk_SK' => 'ISO8859-2',
      'sr_SP' => 'ISO8859-5',
      'Zh_CN' => 'GBK',
      'Sv_SE' => 'IBM-850',
      'sv_SE' => 'ISO8859-1',
      'tr_TR' => 'ISO8859-9',
      'zh_TW' => 'IBM-eucTW'
    );

  // IBM AIX libiconv UTF-8 converters
  // See http://publibn.boulder.ibm.com/doc_link/en_US/a_doc_lib/aixprggd/genprogc/convert_prg.htm#HDRDNNRI49HOWA
  private static $aix_utf8_converters = array
    (
      'ISO8859-1',
      'ISO8859-2',
      'ISO8859-3',
      'ISO8859-4',
      'ISO8859-5',
      'ISO8859-6',
      'ISO8859-7',
      'ISO8859-8',
      'ISO8859-9',
      'JISX0201.1976-0',
      'JISX0208.1983-0',
      'CNS11643.1986-1',
      'CNS11643.1986-2',
      'KSC5601.1987-0',
      'IBM-eucCN',
      'IBM-eucJP',
      'IBM-eucKR',
      'IBM-eucTW',
      'IBM-udcJP',
      'IBM-udcTW',
      'IBM-sbdTW',
      'UCS-2',
      'IBM-437',
      'IBM-850',
      'IBM-852',
      'IBM-857',
      'IBM-860',
      'IBM-861',
      'IBM-863',
      'IBM-865',
      'IBM-869',
      'IBM-921',
      'IBM-922',
      'IBM-932',
      'IBM-943',
      'IBM-934',
      'IBM-935',
      'IBM-936',
      'IBM-938',
      'IBM-942',
      'IBM-944',
      'IBM-946',
      'IBM-948',
      'IBM-1124',
      'IBM-1129',
      'TIS-620',
      'IBM-037',
      'IBM-273',
      'IBM-277',
      'IBM-278',
      'IBM-280',
      'IBM-284',
      'IBM-285',
      'IBM-297',
      'IBM-500',
      'IBM-875',
      'IBM-930',
      'IBM-933',
      'IBM-937',
      'IBM-939',
      'IBM-1026',
      'IBM-1112',
      'IBM-1122',
      'IBM-1124',
      'IBM-1129',
      'IBM-1381',
      'GBK',
      'TIS-620'
    );
    
  // GNU iconv code set to IBM AIX libiconv code set table
  // Keys of this table should be in lowercase, and searches should be performed using lowercase!
  private static $gnu_iconv_to_aix_iconv_codepage_map = array
    (
      // "iso-8859-[1-9]" --> "ISO8859-[1-9]" according to http://publibn.boulder.ibm.com/doc_link/en_US/a_doc_lib/libs/basetrf2/setlocale.htm
      'iso-8859-1' => 'ISO8859-1',
      'iso-8859-2' => 'ISO8859-2',
      'iso-8859-3' => 'ISO8859-3',
      'iso-8859-4' => 'ISO8859-4',
      'iso-8859-5' => 'ISO8859-5',
      'iso-8859-6' => 'ISO8859-6',
      'iso-8859-7' => 'ISO8859-7',
      'iso-8859-8' => 'ISO8859-8',
      'iso-8859-9' => 'ISO8859-9',

      // "big5" --> "IBM-eucTW" according to http://kadesh.cepba.upc.es/mancpp/classref/ref/ITranscoder_DSC.htm
      'big5' => 'IBM-eucTW',

      // "big-5" --> "IBM-eucTW" (see above)
      'big-5' => 'IBM-eucTW'
    );


  public static function getServerOS()
  {
    static $server_os = null;
    
    if (!isset($server_os))
    {
      if (stristr(PHP_OS,'Darwin'))
      {
        $server_os = 'macosx';
      }
      elseif (stristr(PHP_OS, 'WIN'))
      {
        $server_os = 'windows';
      }
      elseif (stristr(PHP_OS, 'Linux'))
      {
        $server_os = 'linux';
      }
      elseif (stristr(PHP_OS, 'BSD'))
      {
        $server_os = 'bsd';
      }
      elseif (stristr(PHP_OS, 'SunOS'))
      {
        $server_os = 'sunos';
      }
      elseif (stristr(PHP_OS, 'AIX'))
      {
        $server_os = 'aix';
      }
      else
      {
        $server_os = 'unsupported';
      }
    }
    
    return $server_os;
  }
  
  
  // Returns a locale in the correct format for the server OS given a BCP 47
  // language tag.
  // Returns FALSE if no locale can be determined
  public static function getOSlocale($langtag)
  {
    $locale = false;
    $server_os = System::getServerOS();
    
    if ($server_os == "windows")
    {
      if (array_key_exists(utf8_strtolower($langtag), self::$lang_map_windows))
      {
        $locale = self::$lang_map_windows[utf8_strtolower($langtag)];
      }
      else
      {
        $message = "Server failed to map browser language '$langtag' to a Windows locale specifier";
        trigger_error($message, E_USER_NOTICE);
      }
    }
    /* All of these Unix OSes work in mostly the same way... */
    else if (in_array($server_os, array('linux', 'sunos', 'bsd', 'aix', 'macosx')))
    {
      if (strlen($langtag) == 2)
      {
        if (isset(self::$lang_map_unix[$langtag]) && (self::$lang_map_unix[$langtag]))
        {
          $locale = self::$lang_map_unix[$langtag];
        }
        else
        {
          // Convert locale=xx to xx_XX
          $locale = utf8_strtolower($langtag) . "_" . utf8_strtoupper($langtag);
        }
      }
      else
      {
        // First, if we have a map, use it
        if (isset(self::$lang_map_unix[utf8_strtolower($langtag)]))
        {
          $locale = self::$lang_map_unix[utf8_strtolower($langtag)];
        }
        else
        {
          $locale = str_replace('-', '_', $langtag);
        }
      }
      switch ($server_os)
      {
        case "sunos":
        case "linux":
        case "bsd":
          $locale .= ".UTF-8";
          if (isset(self::$lang_map_unix_postfix[$locale]))
          { 
            $locale .= self::$lang_map_unix_postfix[$locale];
          }
          break;

        case "macosx":
          $locale .= ".utf-8";
          break;
        default:
          break;
      }
    }
    return $locale;
  }
  
  
  // The inverse of getOSlocale.  Turns an OS-specific locale into a BCP 47 style
  // language tag.    This is provided for backwards compatibility with old versions
  // of MRBS where the $override_locale config setting was required to be operating
  // system specific (eg 'en_GB.utf-8' on Unix systems or 'eng' on Windows).  Now
  // $override_locale should be in BCP 47 format, but we accept old-style settings.
  public static function getBCPlocale($locale)
  {
    $result = $locale;
    
    // Strip anything like '.utf-8' off the end
    if (strpos($result, '.') !== false)
    {
      $result = strstr($locale, '.', true);
    }
    
    // Convert an old style Windows locale, eg 'eng' to a BCP 47 one, eg 'en-gb'
    if ((self::getServerOS() == 'windows') && in_array($result, self::$lang_map_windows))
    {
      $result = array_search($result, self::$lang_map_windows);
    }
    
    // Parse it and then recompose it.  This will get the capitalisation correct, eg
    // "sr-Latn-RS".  Note though that BCP 47 language tags are case insensitive and
    // the capitalisation is just a convention.
    $result = \Locale::composeLocale(\Locale::parseLocale($result));
    
    // Convert underscores to hyphens.  Locale::composeLocale() returns underscores.
    $result = str_replace('_', '-', $result);
    
    return $result;
  }

  
  public static function utf8ConvertFromLocale($string, $locale=null)
  {
    $server_os = System::getServerOS();
    
    if ($server_os == "windows")
    {
      if (!isset($locale))
      {
        $locale = System::getOSlocale(get_mrbs_locale());
      }
      if (array_key_exists($locale, self::$winlocale_codepage_map))
      {
        $string = iconv(self::$winlocale_codepage_map[$locale], 'utf-8',
                        $string);
      }
    }
    else if ($server_os == "aix")
    {
      $string = self::utf8ConvertAix($string, $locale);
    }
    return $string;
  }
  
  
  // AIX version of utf8_convert(); needed as locales won't give us UTF-8
  // NOTE: Should ONLY be called with input encoded in the default code set of the current locale!
  // NOTE: Uses the LC_TIME category for determining the current locale setting, so should preferrably be used on date/time input only!
  private static function utf8ConvertAix($string, $aix_locale = null)
  {
    if (!isset($aix_locale))
    {
      // Retrieve current locale setting
      $aix_locale = setlocale(LC_TIME, '0');
    }

    if ($aix_locale === false)
    {
      // Locale setting could not be retrieved; return string unchanged
      return $string;
    }

    if (!array_key_exists($aix_locale, self::$aixlocale_codepage_map))
    {
      // Default code page of locale could not be found; return string unchanged
      return $string;
    }

    $aix_codepage = self::$aixlocale_codepage_map[$aix_locale];

    if (!in_array($aix_codepage, self::$aix_utf8_converters, true))
    {
      // No suitable UTF-8 converter was found for this code page; return string unchanged
      return $string;
    }

    // Convert string to UTF-8
    $aix_string = iconv($aix_codepage, 'UTF-8', $string);

    // Default to original string if conversion failed
    $string = ($aix_string === false) ? $string : $aix_string;

    return $string;
  }
  
  
  // Translates a GNU libiconv character encoding name to its corresponding IBM AIX libiconv character
  // encoding name. Returns FALSE if character encoding name is unknown.
  private static function getAixCharacterEncoding($character_encoding)
  {
    // Check arguments
    if ($character_encoding == null ||
        !is_string($character_encoding) ||
        empty($character_encoding))
    {
      return false;
    }

    // Convert character encoding name to lowercase
    $character_encoding = utf8_strtolower($character_encoding);

    // Check that we know of an IBM AIX libiconv character encoding name equivalent for this character encoding name
    if (!array_key_exists($character_encoding, self::$gnu_iconv_to_aix_iconv_codepage_map))
    {
      return false;
    }

    return self::$gnu_iconv_to_aix_iconv_codepage_map[$character_encoding];
  }
  
}