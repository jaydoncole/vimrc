<?php
/**
 * Internationalization Functions.
 * See: {@link http://www.php.net/manual/en/book.intl.php}
 * @package intl
 */

# (All dummy values)
define("IDNA_ALLOW_UNASSIGNED", 1);
define("IDNA_CHECK_BIDI", 1);
define("IDNA_CHECK_CONTEXTJ", 1);
define("IDNA_DEFAULT", 1);
define("IDNA_ERROR_BIDI", 1);
define("IDNA_ERROR_CONTEXTJ", 1);
define("IDNA_ERROR_DISALLOWED", 1);
define("IDNA_ERROR_DOMAIN_NAME_TOO_LONG", 1);
define("IDNA_ERROR_EMPTY_LABEL", 1);
define("IDNA_ERROR_HYPHEN_3_4", 1);
define("IDNA_ERROR_INVALID_ACE_LABEL", 1);
define("IDNA_ERROR_LABEL_HAS_DOT", 1);
define("IDNA_ERROR_LABEL_TOO_LONG", 1);
define("IDNA_ERROR_LEADING_COMBINING_MARK", 1);
define("IDNA_ERROR_LEADING_HYPHEN", 1);
define("IDNA_ERROR_PUNYCODE", 1);
define("IDNA_ERROR_TRAILING_HYPHEN", 1);
define("IDNA_NONTRANSITIONAL_TO_ASCII", 1);
define("IDNA_NONTRANSITIONAL_TO_UNICODE", 1);
define("IDNA_USE_STD3_RULES", 1);
define("INTL_IDNA_VARIANT_2003", 1);
define("INTL_IDNA_VARIANT_UTS46", 1);
define("INTL_MAX_LOCALE_LEN", 1);

class Locale {
	# (Dummy values)
	const
		ACTUAL_LOCALE = 1,
		DEFAULT_LOCALE = /*. (string) .*/ NULL,
		EXTLANG_TAG = "?",
		GRANDFATHERED_LANG_TAG = "?",
		LANG_TAG = "?",
		PRIVATE_TAG = "?",
		REGION_TAG = "?",
		SCRIPT_TAG = "?",
		VALID_LOCALE = 1,
		VARIANT_TAG = "?";

	static /*. string .*/ function acceptFromHttp(/*. string .*/ $header){}
	static /*. string .*/ function composeLocale(/*. string[string] .*/ $subtags){}
	static /*. bool .*/ function filterMatches(/*. string .*/ $langtag, /*. string .*/ $locale, /*. bool .*/ $canonicalize = false){}
	static /*. string[int] .*/ function getAllVariants(/*. string .*/ $locale){}
	static /*. string .*/ function getDefault (){}
	static /*. string .*/ function getDisplayLanguage(/*. string .*/ $locale, /*. string .*/ $in_locale = NULL){}
	static /*. string .*/ function getDisplayName(/*. string .*/ $locale, /*. string .*/ $in_locale = NULL){}
	static /*. string .*/ function getDisplayRegion(/*. string .*/ $locale, /*. string .*/ $in_locale = NULL){}
	static /*. string .*/ function getDisplayScript(/*. string .*/ $locale, /*. string .*/ $in_locale = NULL){}
	static /*. string .*/ function getDisplayVariant(/*. string .*/ $locale, /*. string .*/ $in_locale = NULL){}
	static /*. string[string] .*/ function getKeywords(/*. string .*/ $locale){}
	static /*. string .*/ function getPrimaryLanguage(/*. string .*/ $locale){}
	static /*. string .*/ function getRegion(/*. string .*/ $locale){}
	static /*. string .*/ function getScript(/*. string .*/ $locale){}
	static /*. string .*/ function lookup(/*. string[int] .*/ $langtag, /*. string .*/ $locale, /*. bool .*/ $canonicalize = false, /*. string .*/ $default_ = NULL){}
	static /*. string[string] .*/ function parseLocale(/*. string .*/ $locale){}
	static /*. bool .*/ function setDefault(/*. string .*/ $locale){}
}


/*. string .*/ function accept_from_http(/*. string .*/ $header){}
/*. string .*/ function locale_compose(/*. string[string] .*/ $subtags){}
/*. bool .*/ function locale_filter_matches(/*. string .*/ $langtag, /*. string .*/ $locale, /*. bool .*/ $canonicalize = false){}
/*. string[int] .*/ function locale_get_all_variants(/*. string .*/ $locale){}
/*. string .*/ function locale_get_default (){}
/*. string .*/ function locale_get_display_language(/*. string .*/ $locale, /*. string .*/ $in_locale = NULL){}
/*. string .*/ function locale_get_display_name(/*. string .*/ $locale, /*. string .*/ $in_locale = NULL){}
/*. string .*/ function locale_get_display_region(/*. string .*/ $locale, /*. string .*/ $in_locale = NULL){}
/*. string .*/ function locale_get_display_script(/*. string .*/ $locale, /*. string .*/ $in_locale = NULL){}
/*. string .*/ function locale_get_display_variant(/*. string .*/ $locale, /*. string .*/ $in_locale = NULL){}
/*. string[string] .*/ function locale_get_keywords(/*. string .*/ $locale){}
/*. string .*/ function locale_get_primary_language(/*. string .*/ $locale){}
/*. string .*/ function locale_get_region(/*. string .*/ $locale){}
/*. string .*/ function locale_get_script(/*. string .*/ $locale){}
/*. string .*/ function locale_lookup(/*. string[int] .*/ $langtag, /*. string .*/ $locale, /*. bool .*/ $canonicalize = false, /*. string .*/ $default_ = NULL){}
/*. string[string] .*/ function locale_parse(/*. string .*/ $locale){}
/*. bool .*/ function locale_set_default(/*. string .*/ $locale){}


class Collator {

	# (Dummy values)
	const
		ALTERNATE_HANDLING = 1,
		CASE_FIRST = 1,
		CASE_LEVEL = 1,
		DEFAULT_VALUE = 1,
		FRENCH_COLLATION = 1,
		HIRAGANA_QUATERNARY_MODE = 1,
		IDENTICAL = 1,
		LOWER_FIRST = 1,
		NON_IGNORABLE = 1,
		NORMALIZATION_MODE = 1,
		NUMERIC_COLLATION = 1,
		OFF = 1,
		ON = 1,
		PRIMARY = 1,
		QUATERNARY = 1,
		SECONDARY = 1,
		SHIFTED = 1,
		SORT_NUMERIC = 1,
		SORT_REGULAR = 1,
		SORT_STRING = 1,
		STRENGTH = 1,
		TERTIARY = 1,
		UPPER_FIRST = 1;
	
	/*. void .*/ function __construct(/*. string .*/ $locale ){}
	/*. bool .*/ function asort(/*. string[int] .*/ &$arr, $sort_flag = Collator::SORT_REGULAR){}
	/*. int .*/ function compare(/*. string .*/ $str1, /*. string .*/ $str2 ){}
	static /*. Collator .*/ function create(/*. string .*/ $locale ){}
	/*. int .*/ function getAttribute(/*. int .*/ $attr ){}
	/*. int .*/ function getErrorCode(){}
	/*. string .*/ function getErrorMessage(){}
	/*. string .*/ function getLocale(/*. int .*/ $type = Locale::ACTUAL_LOCALE){}
	/*. string .*/ function getSortKey( /*. string .*/ $str ){}
	/*. int .*/ function getStrength(){}
	/*. bool .*/ function setAttribute( /*. int .*/ $attr, /*. int .*/ $val ){}
	/*. bool .*/ function setStrength( /*. int .*/ $strength ){}
	/*. bool .*/ function sortWithSortKeys( /*. string[int] .*/ &$arr ){}
	/*. bool .*/ function sort(/*. string[int] .*/ &$arr, $sort_flag = Collator::SORT_REGULAR){}
}

/*. Collator .*/ function collator_create(/*. string .*/ $locale ){}
/*. bool .*/ function collator_asort(/*. Collator .*/ $coll, /*. string[int] .*/ &$arr, $sort_flag = Collator::SORT_REGULAR){}
/*. int .*/ function collator_compare(/*. Collator .*/ $coll, /*. string .*/ $str1, /*. string .*/ $str2 ){}
/*. int .*/ function collator_get_attribute(/*. Collator .*/ $coll, /*. int .*/ $attr ){}
/*. int .*/ function collator_get_error_code(/*. Collator .*/ $coll){}
/*. string .*/ function collator_get_error_message(/*. Collator .*/ $coll){}
/*. string .*/ function collator_get_locale(/*. Collator .*/ $coll, /*. int .*/ $type = Locale::ACTUAL_LOCALE){}
/*. string .*/ function collator_get_sort_key(/*. Collator .*/ $coll,  /*. string .*/ $str ){}
/*. int .*/ function collator_get_strength(/*. Collator .*/ $coll){}
/*. bool .*/ function collator_set_attribute(/*. Collator .*/ $coll,  /*. int .*/ $attr, /*. int .*/ $val ){}
/*. bool .*/ function collator_set_strength(/*. Collator .*/ $coll,  /*. int .*/ $strength ){}
/*. bool .*/ function collator_sort(/*. Collator .*/ $coll, /*. string[int] .*/ &$arr, $sort_flag = Collator::SORT_REGULAR){}
/*. bool .*/ function collator_sort_with_sort_keys(/*. Collator .*/ $coll,  /*. string[int] .*/ &$arr ){}


class NumberFormatter {

	const
		CURRENCY = 1,
		CURRENCY_CODE = 1,
		CURRENCY_SYMBOL = 1,
		DECIMAL = 1,
		DECIMAL_ALWAYS_SHOWN = 1,
		DECIMAL_SEPARATOR_SYMBOL = 1,
		DEFAULT_RULESET = 1,
		DEFAULT_STYLE = 1,
		DIGIT_SYMBOL = 1,
		DURATION = 1,
		EXPONENTIAL_SYMBOL = 1,
		FORMAT_WIDTH = 1,
		FRACTION_DIGITS = 1,
		GROUPING_SEPARATOR_SYMBOL = 1,
		GROUPING_SIZE = 1,
		GROUPING_USED = 1,
		IGNORE = 1,
		INFINITY_SYMBOL = 1,
		INTEGER_DIGITS = 1,
		INTL_CURRENCY_SYMBOL = 1,
		LENIENT_PARSE = 1,
		MAX_FRACTION_DIGITS = 1,
		MAX_INTEGER_DIGITS = 1,
		MAX_SIGNIFICANT_DIGITS = 1,
		MINUS_SIGN_SYMBOL = 1,
		MIN_FRACTION_DIGITS = 1,
		MIN_INTEGER_DIGITS = 1,
		MIN_SIGNIFICANT_DIGITS = 1,
		MONETARY_GROUPING_SEPARATOR_SYMBOL = 1,
		MONETARY_SEPARATOR_SYMBOL = 1,
		MULTIPLIER = 1,
		NAN_SYMBOL = 1,
		NEGATIVE_PREFIX = 1,
		NEGATIVE_SUFFIX = 1,
		ORDINAL = 1,
		PADDING_CHARACTER = 1,
		PADDING_POSITION = 1,
		PAD_AFTER_PREFIX = 1,
		PAD_AFTER_SUFFIX = 1,
		PAD_BEFORE_PREFIX = 1,
		PAD_BEFORE_SUFFIX = 1,
		PAD_ESCAPE_SYMBOL = 1,
		PARSE_INT_ONLY = 1,
		PATTERN_DECIMAL = 1,
		PATTERN_RULEBASED = 1,
		PATTERN_SEPARATOR_SYMBOL = 1,
		PERCENT = 1,
		PERCENT_SYMBOL = 1,
		PERMILL_SYMBOL = 1,
		PLUS_SIGN_SYMBOL = 1,
		POSITIVE_PREFIX = 1,
		POSITIVE_SUFFIX = 1,
		PUBLIC_RULESETS = 1,
		ROUNDING_INCREMENT = 1,
		ROUNDING_MODE = 1,
		ROUND_CEILING = 1,
		ROUND_DOWN = 1,
		ROUND_FLOOR = 1,
		ROUND_HALFDOWN = 1,
		ROUND_HALFEVEN = 1,
		ROUND_HALFUP = 1,
		ROUND_UP = 1,
		SCIENTIFIC = 1,
		SECONDARY_GROUPING_SIZE = 1,
		SIGNIFICANT_DIGITS_USED = 1,
		SIGNIFICANT_DIGIT_SYMBOL = 1,
		SPELLOUT = 1,
		TYPE_CURRENCY = 1,
		TYPE_DEFAULT = 1,
		TYPE_DOUBLE = 1,
		TYPE_INT32 = 1,
		TYPE_INT64 = 1,
		ZERO_DIGIT_SYMBOL = 1;

	/*. void .*/ function __construct(/*. string .*/ $locale, /*. int .*/ $style, /*. string .*/ $pattern = NULL){}
	static /*. NumberFormatter .*/ function create(/*. string .*/ $locale, /*. int .*/ $style, /*. string .*/ $pattern = NULL){}
	/*. string .*/ function formatCurrency(/*. float .*/ $value, /*. string .*/ $currency){}
	/*. string .*/ function format(/*. float .*/ $value, /*. int .*/ $type = self::TYPE_DEFAULT){}
	/*. int .*/ function getAttribute(/*. int .*/ $attr){}
	/*. int .*/ function getErrorCode(){}
	/*. string .*/ function getErrorMessage(){}
	/*. string .*/ function getLocale(/*. int .*/ $type = Locale::ACTUAL_LOCALE){}
	/*. string .*/ function getPattern(){}
	/*. string .*/ function getSymbol(/*. int .*/ $attr){}
	/*. string .*/ function getTextAttribute(/*. int .*/ $attr){}
	/*. float .*/ function parseCurrency(/*. string .*/ $value, /*. string .*/ &$currency, /*. int .*/ &$position = 0){}
	/*. mixed .*/ function parse(/*. string .*/ $value, /*. int .*/ $type = self::TYPE_DOUBLE, /*. int .*/ &$position = 0){}
	/*. bool .*/ function setAttribute(/*. int .*/ $attr, /*. int .*/ $value){}
	/*. bool .*/ function setPattern(/*. string .*/ $pattern){}
	/*. bool .*/ function setSymbol(/*. int .*/ $attr, /*. string .*/ $value){}
	/*. bool .*/ function setTextAttribute(/*. int .*/ $attr, /*. string .*/ $value){}
}


/*. NumberFormatter .*/ function numfmt_create(/*. string .*/ $locale, /*. int .*/ $style, /*. string .*/ $pattern = NULL){}
/*. string .*/ function numfmt_format_currency(/*. NumberFormatter .*/ $fmt, /*. float .*/ $value, /*. string .*/ $currency){}
/*. string .*/ function numfmt_format(/*. NumberFormatter .*/ $fmt, /*. float .*/ $value, /*. int .*/ $type = NumberFormatter::TYPE_DEFAULT){}
/*. int .*/ function numfmt_get_attribute(/*. NumberFormatter .*/ $fmt, /*. int .*/ $attr){}
/*. int .*/ function numfmt_get_error_code(/*. NumberFormatter .*/ $fmt){}
/*. string .*/ function numfmt_get_error_message(/*. NumberFormatter .*/ $fmt){}
/*. string .*/ function numfmt_get_locale(/*. NumberFormatter .*/ $fmt, /*. int .*/ $type = Locale::ACTUAL_LOCALE){}
/*. string .*/ function numfmt_get_pattern(/*. NumberFormatter .*/ $fmt){}
/*. string .*/ function numfmt_get_symbol(/*. NumberFormatter .*/ $fmt, /*. int .*/ $attr){}
/*. string .*/ function numfmt_get_text_attribute(/*. NumberFormatter .*/ $fmt, /*. int .*/ $attr){}
/*. float .*/ function numfmt_parse_currency(/*. NumberFormatter .*/ $fmt, /*. string .*/ $value, /*. string .*/ &$currency, /*. int .*/ &$position = 0){}
/*. mixed .*/ function numfmt_parse(/*. NumberFormatter .*/ $fmt, /*. string .*/ $value, /*. int .*/ $type = NumberFormatter::TYPE_DOUBLE, /*. int .*/ &$position = 0){}
/*. bool .*/ function numfmt_set_attribute(/*. NumberFormatter .*/ $fmt, /*. int .*/ $attr, /*. int .*/ $value){}
/*. bool .*/ function numfmt_set_pattern(/*. NumberFormatter .*/ $fmt, /*. string .*/ $pattern){}
/*. bool .*/ function numfmt_set_symbol(/*. NumberFormatter .*/ $fmt, /*. int .*/ $attr, /*. string .*/ $value){}
/*. bool .*/ function numfmt_set_text_attribute(/*. NumberFormatter .*/ $fmt, /*. int .*/ $attr, /*. string .*/ $value){}



class Normalizer {

	const
		FORM_C = "",
		FORM_D = "",
		FORM_KC = "",
		FORM_KD = "",
		NONE = "",
		OPTION_DEFAULT = "";

	static /*. bool .*/ function isNormalized(/*. string .*/ $input, $form = Normalizer::FORM_C){}
	static /*. string .*/ function normalize(/*. string .*/ $input, $form = Normalizer::FORM_C){}
}


/*. bool .*/ function normalizer_is_normalized(/*. string .*/ $input, $form = Normalizer::FORM_C){}
/*. string .*/ function normalizer_normalize(/*. string .*/ $input, $form = Normalizer::FORM_C){}


class MessageFormatter {
	/*. void .*/ function __construct(/*. string .*/ $locale, /*. string .*/ $pattern){}
	static /*. MessageFormatter .*/ function create(/*. string .*/ $locale, /*. string .*/ $pattern){}
	static /*. string .*/ function formatMessage(/*. string .*/ $locale, /*. string .*/ $pattern, /*. array .*/ $args_){}
	/*. string .*/ function format(/*. array .*/ $args_){}
	/*. int .*/ function getErrorCode(){}
	/*. string .*/ function getErrorMessage(){}
	/*. string .*/ function getLocale(){}
	/*. string .*/ function getPattern(){}
	static /*. mixed[int] .*/ function parseMessage(/*. string .*/ $locale, /*. string .*/ $pattern, /*. string .*/ $source){}
	/*. mixed[int] .*/ function parse(/*. string .*/ $value){}
	/*. bool .*/ function setPattern(/*. string .*/ $pattern){}
}


/*. MessageFormatter .*/ function msgfmt_create(/*. string .*/ $locale, /*. string .*/ $pattern){}
/*. string .*/ function msgfmt_format_message(/*. string .*/ $locale, /*. string .*/ $pattern, /*. array .*/ $args_){}
/*. string .*/ function msgfmt_format(/*. MessageFormatter .*/ $fmt, /*. array .*/ $args_){}
/*. int .*/ function msgfmt_get_error_code(/*. MessageFormatter .*/ $fmt){}
/*. string .*/ function msgfmt_get_error_message(/*. MessageFormatter .*/ $fmt){}
/*. string .*/ function msgfmt_get_locale(/*. MessageFormatter .*/ $fmt){}
/*. string .*/ function msgfmt_get_pattern(/*. MessageFormatter .*/ $fmt){}
/*. mixed[int] .*/ function msgfmt_parse_message(/*. string .*/ $locale, /*. string .*/ $pattern, /*. string .*/ $source){}
/*. mixed[int] .*/ function msgfmt_parse(/*. MessageFormatter .*/ $fmt, /*. string .*/ $value){}
/*. bool .*/ function msgfmt_set_pattern(/*. MessageFormatter .*/ $fmt, /*. string .*/ $pattern){}


class IntlDateFormatter {

	const
		NONE = 1,
		FULL = 1,
		LONG = 1,
		MEDIUM = 1,
		SHORT = 1,
		TRADITIONAL = 1,
		GREGORIAN = 1;

	/*. void .*/ function __construct(/*. string .*/ $locale, /*. int .*/ $datetype, /*. int .*/ $timetype, $timezone = "", $calendar = IntlDateFormatter::GREGORIAN, /*. string .*/ $pattern = ""){}
	static /*. IntlDateFormatter .*/ function create(/*. string .*/ $locale, /*. int .*/ $datetype, /*. int .*/ $timetype, $timezone = "", $calendar = IntlDateFormatter::GREGORIAN, /*. string .*/ $pattern = ""){}
	/*. string .*/ function format(/*. mixed .*/ $value){}
	/*. int .*/ function getCalendar(){}
	/*. int .*/ function getDateType(){}
	/*. int .*/ function getErrorCode(){}
	/*. string .*/ function getErrorMessage(){}
	/*. string .*/ function getLocale($which = Locale::ACTUAL_LOCALE){}
	/*. string .*/ function getPattern(){}
	/*. int .*/ function getTimeType(){}
	/*. string .*/ function getTimeZoneId(){}
	/*. bool .*/ function isLenient(){}
	/*. int[string] .*/ function localtime(/*. string .*/ $value, & $position = 0){}
	/*. int .*/ function parse(/*. string .*/ $value, & $position = 0){}
	/*. bool .*/ function setCalendar(/*. int .*/ $which){}
	/*. bool .*/ function setLenient(/*. bool .*/ $lenient){}
	/*. bool .*/ function setPattern(/*. string .*/ $pattern){}
	/*. bool .*/ function setTimeZoneId(/*. string .*/ $zone){}
}


class ResourceBundle {
	/* Methods */
	/*. void .*/ function __construct(/*. string .*/ $locale, /*. string .*/ $bundlename, $fallback = FALSE){}
	/*. int .*/ function count(){}
	static /*. ResourceBundle .*/ function create(/*. string .*/ $locale, /*. string .*/ $bundlename, $fallback = FALSE){}
	/*. int .*/ function getErrorCode(){}
	/*. string .*/ function getErrorMessage(){}
	/*. mixed .*/ function get(/*. mixed .*/ $index){}
	/*. string[int] .*/ function getLocales(/*. string .*/ $bundlename){}
}



class Spoofchecker {

	const
		SINGLE_SCRIPT_CONFUSABLE = 1,
		MIXED_SCRIPT_CONFUSABLE = 2,
		WHOLE_SCRIPT_CONFUSABLE = 4,
		ANY_CASE = 8,
		SINGLE_SCRIPT = 16,
		INVISIBLE = 32,
		CHAR_LIMIT = 64;

	/*. bool .*/ function areConfusable(/*. string .*/ $s1, /*. string .*/ $s2, /*. return .*/ & $error = ""){}
	/*. void .*/ function __construct(){}
	/*. bool .*/ function isSuspicious(/*. string .*/ $text, /*. return .*/ & $error = ""){}
	/*. void .*/ function setAllowedLocales(/*. string .*/ $locale_list){}
	/*. void .*/ function setChecks(/*. string .*/ $checks){}
}


class Transliterator {

	const
		FORWARD_FIXME = 0,
		REVERSE = 1 ;

	public /*. string .*/ $id;

	/*. void .*/ function __construct(){}
	static /*. Transliterator .*/ function create(/*. string .*/ $id, $direction = Transliterator::FORWARD_FIXME){}
	static /*. Transliterator .*/ function createFromRules(/*. string .*/ $rules, $direction = Transliterator::FORWARD_FIXME){}
	/*. Transliterator .*/ function createInverse(){}
	/*. int .*/ function getErrorCode(){}
	/*. string .*/ function getErrorMessage(){}
	static /*. string[int] .*/ function listIDs(){}
	/*. string .*/ function transliterate(/*. string .*/ $subject, $start = 0, $end = 999999999){}
}


# Dummy values:
define("GRAPHEME_EXTR_COUNT", 1);
define("GRAPHEME_EXTR_MAXBYTES", 1);
define("GRAPHEME_EXTR_MAXCHARS", 1);


/*. string .*/ function grapheme_extract(/*. string .*/ $haystack, /*. int .*/ $size, $extract_type = GRAPHEME_EXTR_COUNT, $start = 0, &$next = 0){}
/*. string .*/ function grapheme_stripos(/*. string .*/ $haystack, /*. string .*/ $needle, $offset = 0){}
/*. string .*/ function grapheme_stristr(/*. string .*/ $haystack, /*. string .*/ $needle, $before_needle = false){}
/*. string .*/ function grapheme_strlen(/*. string .*/ $input){}
/*. int .*/ function grapheme_strpos(/*. string .*/ $haystack, /*. string .*/ $needle, $offset = 0){}
/*. int .*/ function grapheme_strripos(/*. string .*/ $haystack, /*. string .*/ $needle, $offset = 0){}
/*. int .*/ function grapheme_strrpos(/*. string .*/ $haystack, /*. string .*/ $needle, $offset = 0){}
/*. string .*/ function grapheme_strstr(/*. string .*/ $haystack, /*. string .*/ $needle, $before_needle = false){}
/*. string .*/ function grapheme_substr(/*. string .*/ $string_, /*. int .*/ $start, $lengthi = 0){}


/*. string .*/ function idn_to_ascii ( /*. string .*/ $domain, $options = 0, $variant = INTL_IDNA_VARIANT_2003, /*. array .*/ &$idna_info = NULL){}
/*. string .*/ function idn_to_unicode( /*. string .*/ $domain, $options = 0, $variant = INTL_IDNA_VARIANT_2003, /*. array .*/ &$idna_info = NULL){}
/*. string .*/ function idn_to_utf8 ( /*. string .*/ $domain, $options = 0, $variant = INTL_IDNA_VARIANT_2003, /*. array .*/ &$idna_info = NULL){}


/*. string .*/ function intl_error_name(/*. int .*/ $error_code){}
/*. int .*/ function intl_get_error_code(){}
/*. string .*/ function intl_get_error_message(){}
/*. bool .*/ function intl_is_failure( /*. int .*/ $error_code ){}

/*. if_php_ver_7 .*/

class IntlChar {

const UNICODE_VERSION = 6.3,
	CODEPOINT_MIN = 0,
	CODEPOINT_MAX = 1114111,
	PROPERTY_ALPHABETIC = 0,
	PROPERTY_BINARY_START = 0,
	PROPERTY_ASCII_HEX_DIGIT = 1,
	PROPERTY_BIDI_CONTROL = 2,
	PROPERTY_BIDI_MIRRORED = 3,
	PROPERTY_DASH = 4,
	PROPERTY_DEFAULT_IGNORABLE_CODE_POINT = 5,
	PROPERTY_DEPRECATED = 6,
	PROPERTY_DIACRITIC = 7,
	PROPERTY_EXTENDER = 8,
	PROPERTY_FULL_COMPOSITION_EXCLUSION = 9,
	PROPERTY_GRAPHEME_BASE = 10,
	PROPERTY_GRAPHEME_EXTEND = 11,
	PROPERTY_GRAPHEME_LINK = 12,
	PROPERTY_HEX_DIGIT = 13,
	PROPERTY_HYPHEN = 14,
	PROPERTY_ID_CONTINUE = 15,
	PROPERTY_ID_START = 16,
	PROPERTY_IDEOGRAPHIC = 17,
	PROPERTY_IDS_BINARY_OPERATOR = 18,
	PROPERTY_IDS_TRINARY_OPERATOR = 19,
	PROPERTY_JOIN_CONTROL = 20,
	PROPERTY_LOGICAL_ORDER_EXCEPTION = 21,
	PROPERTY_LOWERCASE = 22,
	PROPERTY_MATH = 23,
	PROPERTY_NONCHARACTER_CODE_POINT = 24,
	PROPERTY_QUOTATION_MARK = 25,
	PROPERTY_RADICAL = 26,
	PROPERTY_SOFT_DOTTED = 27,
	PROPERTY_TERMINAL_PUNCTUATION = 28,
	PROPERTY_UNIFIED_IDEOGRAPH = 29,
	PROPERTY_UPPERCASE = 30,
	PROPERTY_WHITE_SPACE = 31,
	PROPERTY_XID_CONTINUE = 32,
	PROPERTY_XID_START = 33,
	PROPERTY_CASE_SENSITIVE = 34,
	PROPERTY_S_TERM = 35,
	PROPERTY_VARIATION_SELECTOR = 36,
	PROPERTY_NFD_INERT = 37,
	PROPERTY_NFKD_INERT = 38,
	PROPERTY_NFC_INERT = 39,
	PROPERTY_NFKC_INERT = 40,
	PROPERTY_SEGMENT_STARTER = 41,
	PROPERTY_PATTERN_SYNTAX = 42,
	PROPERTY_PATTERN_WHITE_SPACE = 43,
	PROPERTY_POSIX_ALNUM = 44,
	PROPERTY_POSIX_BLANK = 45,
	PROPERTY_POSIX_GRAPH = 46,
	PROPERTY_POSIX_PRINT = 47,
	PROPERTY_POSIX_XDIGIT = 48,
	PROPERTY_CASED = 49,
	PROPERTY_CASE_IGNORABLE = 50,
	PROPERTY_CHANGES_WHEN_LOWERCASED = 51,
	PROPERTY_CHANGES_WHEN_UPPERCASED = 52,
	PROPERTY_CHANGES_WHEN_TITLECASED = 53,
	PROPERTY_CHANGES_WHEN_CASEFOLDED = 54,
	PROPERTY_CHANGES_WHEN_CASEMAPPED = 55,
	PROPERTY_CHANGES_WHEN_NFKC_CASEFOLDED = 56,
	PROPERTY_BINARY_LIMIT = 57,
	PROPERTY_BIDI_CLASS = 4096,
	PROPERTY_INT_START = 4096,
	PROPERTY_BLOCK = 4097,
	PROPERTY_CANONICAL_COMBINING_CLASS = 4098,
	PROPERTY_DECOMPOSITION_TYPE = 4099,
	PROPERTY_EAST_ASIAN_WIDTH = 4100,
	PROPERTY_GENERAL_CATEGORY = 4101,
	PROPERTY_JOINING_GROUP = 4102,
	PROPERTY_JOINING_TYPE = 4103,
	PROPERTY_LINE_BREAK = 4104,
	PROPERTY_NUMERIC_TYPE = 4105,
	PROPERTY_SCRIPT = 4106,
	PROPERTY_HANGUL_SYLLABLE_TYPE = 4107,
	PROPERTY_NFD_QUICK_CHECK = 4108,
	PROPERTY_NFKD_QUICK_CHECK = 4109,
	PROPERTY_NFC_QUICK_CHECK = 4110,
	PROPERTY_NFKC_QUICK_CHECK = 4111,
	PROPERTY_LEAD_CANONICAL_COMBINING_CLASS = 4112,
	PROPERTY_TRAIL_CANONICAL_COMBINING_CLASS = 4113,
	PROPERTY_GRAPHEME_CLUSTER_BREAK = 4114,
	PROPERTY_SENTENCE_BREAK = 4115,
	PROPERTY_WORD_BREAK = 4116,
	PROPERTY_BIDI_PAIRED_BRACKET_TYPE = 4117,
	PROPERTY_INT_LIMIT = 4118,
	PROPERTY_GENERAL_CATEGORY_MASK = 8192,
	PROPERTY_MASK_START = 8192,
	PROPERTY_MASK_LIMIT = 8193,
	PROPERTY_NUMERIC_VALUE = 12288,
	PROPERTY_DOUBLE_START = 12288,
	PROPERTY_DOUBLE_LIMIT = 12289,
	PROPERTY_AGE = 16384,
	PROPERTY_STRING_START = 16384,
	PROPERTY_BIDI_MIRRORING_GLYPH = 16385,
	PROPERTY_CASE_FOLDING = 16386,
	PROPERTY_ISO_COMMENT = 16387,
	PROPERTY_LOWERCASE_MAPPING = 16388,
	PROPERTY_NAME = 16389,
	PROPERTY_SIMPLE_CASE_FOLDING = 16390,
	PROPERTY_SIMPLE_LOWERCASE_MAPPING = 16391,
	PROPERTY_SIMPLE_TITLECASE_MAPPING = 16392,
	PROPERTY_SIMPLE_UPPERCASE_MAPPING = 16393,
	PROPERTY_TITLECASE_MAPPING = 16394,
	PROPERTY_UNICODE_1_NAME = 16395,
	PROPERTY_UPPERCASE_MAPPING = 16396,
	PROPERTY_BIDI_PAIRED_BRACKET = 16397,
	PROPERTY_STRING_LIMIT = 16398,
	PROPERTY_SCRIPT_EXTENSIONS = 28672,
	PROPERTY_OTHER_PROPERTY_START = 28672,
	PROPERTY_OTHER_PROPERTY_LIMIT = 28673,
	PROPERTY_INVALID_CODE = -1,
	CHAR_CATEGORY_UNASSIGNED = 0,
	CHAR_CATEGORY_GENERAL_OTHER_TYPES = 0,
	CHAR_CATEGORY_UPPERCASE_LETTER = 1,
	CHAR_CATEGORY_LOWERCASE_LETTER = 2,
	CHAR_CATEGORY_TITLECASE_LETTER = 3,
	CHAR_CATEGORY_MODIFIER_LETTER = 4,
	CHAR_CATEGORY_OTHER_LETTER = 5,
	CHAR_CATEGORY_NON_SPACING_MARK = 6,
	CHAR_CATEGORY_ENCLOSING_MARK = 7,
	CHAR_CATEGORY_COMBINING_SPACING_MARK = 8,
	CHAR_CATEGORY_DECIMAL_DIGIT_NUMBER = 9,
	CHAR_CATEGORY_LETTER_NUMBER = 10,
	CHAR_CATEGORY_OTHER_NUMBER = 11,
	CHAR_CATEGORY_SPACE_SEPARATOR = 12,
	CHAR_CATEGORY_LINE_SEPARATOR = 13,
	CHAR_CATEGORY_PARAGRAPH_SEPARATOR = 14,
	CHAR_CATEGORY_CONTROL_CHAR = 15,
	CHAR_CATEGORY_FORMAT_CHAR = 16,
	CHAR_CATEGORY_PRIVATE_USE_CHAR = 17,
	CHAR_CATEGORY_SURROGATE = 18,
	CHAR_CATEGORY_DASH_PUNCTUATION = 19,
	CHAR_CATEGORY_START_PUNCTUATION = 20,
	CHAR_CATEGORY_END_PUNCTUATION = 21,
	CHAR_CATEGORY_CONNECTOR_PUNCTUATION = 22,
	CHAR_CATEGORY_OTHER_PUNCTUATION = 23,
	CHAR_CATEGORY_MATH_SYMBOL = 24,
	CHAR_CATEGORY_CURRENCY_SYMBOL = 25,
	CHAR_CATEGORY_MODIFIER_SYMBOL = 26,
	CHAR_CATEGORY_OTHER_SYMBOL = 27,
	CHAR_CATEGORY_INITIAL_PUNCTUATION = 28,
	CHAR_CATEGORY_FINAL_PUNCTUATION = 29,
	CHAR_CATEGORY_CHAR_CATEGORY_COUNT = 30,
	CHAR_DIRECTION_LEFT_TO_RIGHT = 0,
	CHAR_DIRECTION_RIGHT_TO_LEFT = 1,
	CHAR_DIRECTION_EUROPEAN_NUMBER = 2,
	CHAR_DIRECTION_EUROPEAN_NUMBER_SEPARATOR = 3,
	CHAR_DIRECTION_EUROPEAN_NUMBER_TERMINATOR = 4,
	CHAR_DIRECTION_ARABIC_NUMBER = 5,
	CHAR_DIRECTION_COMMON_NUMBER_SEPARATOR = 6,
	CHAR_DIRECTION_BLOCK_SEPARATOR = 7,
	CHAR_DIRECTION_SEGMENT_SEPARATOR = 8,
	CHAR_DIRECTION_WHITE_SPACE_NEUTRAL = 9,
	CHAR_DIRECTION_OTHER_NEUTRAL = 10,
	CHAR_DIRECTION_LEFT_TO_RIGHT_EMBEDDING = 11,
	CHAR_DIRECTION_LEFT_TO_RIGHT_OVERRIDE = 12,
	CHAR_DIRECTION_RIGHT_TO_LEFT_ARABIC = 13,
	CHAR_DIRECTION_RIGHT_TO_LEFT_EMBEDDING = 14,
	CHAR_DIRECTION_RIGHT_TO_LEFT_OVERRIDE = 15,
	CHAR_DIRECTION_POP_DIRECTIONAL_FORMAT = 16,
	CHAR_DIRECTION_DIR_NON_SPACING_MARK = 17,
	CHAR_DIRECTION_BOUNDARY_NEUTRAL = 18,
	CHAR_DIRECTION_FIRST_STRONG_ISOLATE = 19,
	CHAR_DIRECTION_LEFT_TO_RIGHT_ISOLATE = 20,
	CHAR_DIRECTION_RIGHT_TO_LEFT_ISOLATE = 21,
	CHAR_DIRECTION_POP_DIRECTIONAL_ISOLATE = 22,
	CHAR_DIRECTION_CHAR_DIRECTION_COUNT = 23,
	BLOCK_CODE_NO_BLOCK = 0,
	BLOCK_CODE_BASIC_LATIN = 1,
	BLOCK_CODE_LATIN_1_SUPPLEMENT = 2,
	BLOCK_CODE_LATIN_EXTENDED_A = 3,
	BLOCK_CODE_LATIN_EXTENDED_B = 4,
	BLOCK_CODE_IPA_EXTENSIONS = 5,
	BLOCK_CODE_SPACING_MODIFIER_LETTERS = 6,
	BLOCK_CODE_COMBINING_DIACRITICAL_MARKS = 7,
	BLOCK_CODE_GREEK = 8,
	BLOCK_CODE_CYRILLIC = 9,
	BLOCK_CODE_ARMENIAN = 10,
	BLOCK_CODE_HEBREW = 11,
	BLOCK_CODE_ARABIC = 12,
	BLOCK_CODE_SYRIAC = 13,
	BLOCK_CODE_THAANA = 14,
	BLOCK_CODE_DEVANAGARI = 15,
	BLOCK_CODE_BENGALI = 16,
	BLOCK_CODE_GURMUKHI = 17,
	BLOCK_CODE_GUJARATI = 18,
	BLOCK_CODE_ORIYA = 19,
	BLOCK_CODE_TAMIL = 20,
	BLOCK_CODE_TELUGU = 21,
	BLOCK_CODE_KANNADA = 22,
	BLOCK_CODE_MALAYALAM = 23,
	BLOCK_CODE_SINHALA = 24,
	BLOCK_CODE_THAI = 25,
	BLOCK_CODE_LAO = 26,
	BLOCK_CODE_TIBETAN = 27,
	BLOCK_CODE_MYANMAR = 28,
	BLOCK_CODE_GEORGIAN = 29,
	BLOCK_CODE_HANGUL_JAMO = 30,
	BLOCK_CODE_ETHIOPIC = 31,
	BLOCK_CODE_CHEROKEE = 32,
	BLOCK_CODE_UNIFIED_CANADIAN_ABORIGINAL_SYLLABICS = 33,
	BLOCK_CODE_OGHAM = 34,
	BLOCK_CODE_RUNIC = 35,
	BLOCK_CODE_KHMER = 36,
	BLOCK_CODE_MONGOLIAN = 37,
	BLOCK_CODE_LATIN_EXTENDED_ADDITIONAL = 38,
	BLOCK_CODE_GREEK_EXTENDED = 39,
	BLOCK_CODE_GENERAL_PUNCTUATION = 40,
	BLOCK_CODE_SUPERSCRIPTS_AND_SUBSCRIPTS = 41,
	BLOCK_CODE_CURRENCY_SYMBOLS = 42,
	BLOCK_CODE_COMBINING_MARKS_FOR_SYMBOLS = 43,
	BLOCK_CODE_LETTERLIKE_SYMBOLS = 44,
	BLOCK_CODE_NUMBER_FORMS = 45,
	BLOCK_CODE_ARROWS = 46,
	BLOCK_CODE_MATHEMATICAL_OPERATORS = 47,
	BLOCK_CODE_MISCELLANEOUS_TECHNICAL = 48,
	BLOCK_CODE_CONTROL_PICTURES = 49,
	BLOCK_CODE_OPTICAL_CHARACTER_RECOGNITION = 50,
	BLOCK_CODE_ENCLOSED_ALPHANUMERICS = 51,
	BLOCK_CODE_BOX_DRAWING = 52,
	BLOCK_CODE_BLOCK_ELEMENTS = 53,
	BLOCK_CODE_GEOMETRIC_SHAPES = 54,
	BLOCK_CODE_MISCELLANEOUS_SYMBOLS = 55,
	BLOCK_CODE_DINGBATS = 56,
	BLOCK_CODE_BRAILLE_PATTERNS = 57,
	BLOCK_CODE_CJK_RADICALS_SUPPLEMENT = 58,
	BLOCK_CODE_KANGXI_RADICALS = 59,
	BLOCK_CODE_IDEOGRAPHIC_DESCRIPTION_CHARACTERS = 60,
	BLOCK_CODE_CJK_SYMBOLS_AND_PUNCTUATION = 61,
	BLOCK_CODE_HIRAGANA = 62,
	BLOCK_CODE_KATAKANA = 63,
	BLOCK_CODE_BOPOMOFO = 64,
	BLOCK_CODE_HANGUL_COMPATIBILITY_JAMO = 65,
	BLOCK_CODE_KANBUN = 66,
	BLOCK_CODE_BOPOMOFO_EXTENDED = 67,
	BLOCK_CODE_ENCLOSED_CJK_LETTERS_AND_MONTHS = 68,
	BLOCK_CODE_CJK_COMPATIBILITY = 69,
	BLOCK_CODE_CJK_UNIFIED_IDEOGRAPHS_EXTENSION_A = 70,
	BLOCK_CODE_CJK_UNIFIED_IDEOGRAPHS = 71,
	BLOCK_CODE_YI_SYLLABLES = 72,
	BLOCK_CODE_YI_RADICALS = 73,
	BLOCK_CODE_HANGUL_SYLLABLES = 74,
	BLOCK_CODE_HIGH_SURROGATES = 75,
	BLOCK_CODE_HIGH_PRIVATE_USE_SURROGATES = 76,
	BLOCK_CODE_LOW_SURROGATES = 77,
	BLOCK_CODE_PRIVATE_USE_AREA = 78,
	BLOCK_CODE_PRIVATE_USE = 78,
	BLOCK_CODE_CJK_COMPATIBILITY_IDEOGRAPHS = 79,
	BLOCK_CODE_ALPHABETIC_PRESENTATION_FORMS = 80,
	BLOCK_CODE_ARABIC_PRESENTATION_FORMS_A = 81,
	BLOCK_CODE_COMBINING_HALF_MARKS = 82,
	BLOCK_CODE_CJK_COMPATIBILITY_FORMS = 83,
	BLOCK_CODE_SMALL_FORM_VARIANTS = 84,
	BLOCK_CODE_ARABIC_PRESENTATION_FORMS_B = 85,
	BLOCK_CODE_SPECIALS = 86,
	BLOCK_CODE_HALFWIDTH_AND_FULLWIDTH_FORMS = 87,
	BLOCK_CODE_OLD_ITALIC = 88,
	BLOCK_CODE_GOTHIC = 89,
	BLOCK_CODE_DESERET = 90,
	BLOCK_CODE_BYZANTINE_MUSICAL_SYMBOLS = 91,
	BLOCK_CODE_MUSICAL_SYMBOLS = 92,
	BLOCK_CODE_MATHEMATICAL_ALPHANUMERIC_SYMBOLS = 93,
	BLOCK_CODE_CJK_UNIFIED_IDEOGRAPHS_EXTENSION_B = 94,
	BLOCK_CODE_CJK_COMPATIBILITY_IDEOGRAPHS_SUPPLEMENT = 95,
	BLOCK_CODE_TAGS = 96,
	BLOCK_CODE_CYRILLIC_SUPPLEMENT = 97,
	BLOCK_CODE_CYRILLIC_SUPPLEMENTARY = 97,
	BLOCK_CODE_TAGALOG = 98,
	BLOCK_CODE_HANUNOO = 99,
	BLOCK_CODE_BUHID = 100,
	BLOCK_CODE_TAGBANWA = 101,
	BLOCK_CODE_MISCELLANEOUS_MATHEMATICAL_SYMBOLS_A = 102,
	BLOCK_CODE_SUPPLEMENTAL_ARROWS_A = 103,
	BLOCK_CODE_SUPPLEMENTAL_ARROWS_B = 104,
	BLOCK_CODE_MISCELLANEOUS_MATHEMATICAL_SYMBOLS_B = 105,
	BLOCK_CODE_SUPPLEMENTAL_MATHEMATICAL_OPERATORS = 106,
	BLOCK_CODE_KATAKANA_PHONETIC_EXTENSIONS = 107,
	BLOCK_CODE_VARIATION_SELECTORS = 108,
	BLOCK_CODE_SUPPLEMENTARY_PRIVATE_USE_AREA_A = 109,
	BLOCK_CODE_SUPPLEMENTARY_PRIVATE_USE_AREA_B = 110,
	BLOCK_CODE_LIMBU = 111,
	BLOCK_CODE_TAI_LE = 112,
	BLOCK_CODE_KHMER_SYMBOLS = 113,
	BLOCK_CODE_PHONETIC_EXTENSIONS = 114,
	BLOCK_CODE_MISCELLANEOUS_SYMBOLS_AND_ARROWS = 115,
	BLOCK_CODE_YIJING_HEXAGRAM_SYMBOLS = 116,
	BLOCK_CODE_LINEAR_B_SYLLABARY = 117,
	BLOCK_CODE_LINEAR_B_IDEOGRAMS = 118,
	BLOCK_CODE_AEGEAN_NUMBERS = 119,
	BLOCK_CODE_UGARITIC = 120,
	BLOCK_CODE_SHAVIAN = 121,
	BLOCK_CODE_OSMANYA = 122,
	BLOCK_CODE_CYPRIOT_SYLLABARY = 123,
	BLOCK_CODE_TAI_XUAN_JING_SYMBOLS = 124,
	BLOCK_CODE_VARIATION_SELECTORS_SUPPLEMENT = 125,
	BLOCK_CODE_ANCIENT_GREEK_MUSICAL_NOTATION = 126,
	BLOCK_CODE_ANCIENT_GREEK_NUMBERS = 127,
	BLOCK_CODE_ARABIC_SUPPLEMENT = 128,
	BLOCK_CODE_BUGINESE = 129,
	BLOCK_CODE_CJK_STROKES = 130,
	BLOCK_CODE_COMBINING_DIACRITICAL_MARKS_SUPPLEMENT = 131,
	BLOCK_CODE_COPTIC = 132,
	BLOCK_CODE_ETHIOPIC_EXTENDED = 133,
	BLOCK_CODE_ETHIOPIC_SUPPLEMENT = 134,
	BLOCK_CODE_GEORGIAN_SUPPLEMENT = 135,
	BLOCK_CODE_GLAGOLITIC = 136,
	BLOCK_CODE_KHAROSHTHI = 137,
	BLOCK_CODE_MODIFIER_TONE_LETTERS = 138,
	BLOCK_CODE_NEW_TAI_LUE = 139,
	BLOCK_CODE_OLD_PERSIAN = 140,
	BLOCK_CODE_PHONETIC_EXTENSIONS_SUPPLEMENT = 141,
	BLOCK_CODE_SUPPLEMENTAL_PUNCTUATION = 142,
	BLOCK_CODE_SYLOTI_NAGRI = 143,
	BLOCK_CODE_TIFINAGH = 144,
	BLOCK_CODE_VERTICAL_FORMS = 145,
	BLOCK_CODE_NKO = 146,
	BLOCK_CODE_BALINESE = 147,
	BLOCK_CODE_LATIN_EXTENDED_C = 148,
	BLOCK_CODE_LATIN_EXTENDED_D = 149,
	BLOCK_CODE_PHAGS_PA = 150,
	BLOCK_CODE_PHOENICIAN = 151,
	BLOCK_CODE_CUNEIFORM = 152,
	BLOCK_CODE_CUNEIFORM_NUMBERS_AND_PUNCTUATION = 153,
	BLOCK_CODE_COUNTING_ROD_NUMERALS = 154,
	BLOCK_CODE_SUNDANESE = 155,
	BLOCK_CODE_LEPCHA = 156,
	BLOCK_CODE_OL_CHIKI = 157,
	BLOCK_CODE_CYRILLIC_EXTENDED_A = 158,
	BLOCK_CODE_VAI = 159,
	BLOCK_CODE_CYRILLIC_EXTENDED_B = 160,
	BLOCK_CODE_SAURASHTRA = 161,
	BLOCK_CODE_KAYAH_LI = 162,
	BLOCK_CODE_REJANG = 163,
	BLOCK_CODE_CHAM = 164,
	BLOCK_CODE_ANCIENT_SYMBOLS = 165,
	BLOCK_CODE_PHAISTOS_DISC = 166,
	BLOCK_CODE_LYCIAN = 167,
	BLOCK_CODE_CARIAN = 168,
	BLOCK_CODE_LYDIAN = 169,
	BLOCK_CODE_MAHJONG_TILES = 170,
	BLOCK_CODE_DOMINO_TILES = 171,
	BLOCK_CODE_SAMARITAN = 172,
	BLOCK_CODE_UNIFIED_CANADIAN_ABORIGINAL_SYLLABICS_EXTENDED = 173,
	BLOCK_CODE_TAI_THAM = 174,
	BLOCK_CODE_VEDIC_EXTENSIONS = 175,
	BLOCK_CODE_LISU = 176,
	BLOCK_CODE_BAMUM = 177,
	BLOCK_CODE_COMMON_INDIC_NUMBER_FORMS = 178,
	BLOCK_CODE_DEVANAGARI_EXTENDED = 179,
	BLOCK_CODE_HANGUL_JAMO_EXTENDED_A = 180,
	BLOCK_CODE_JAVANESE = 181,
	BLOCK_CODE_MYANMAR_EXTENDED_A = 182,
	BLOCK_CODE_TAI_VIET = 183,
	BLOCK_CODE_MEETEI_MAYEK = 184,
	BLOCK_CODE_HANGUL_JAMO_EXTENDED_B = 185,
	BLOCK_CODE_IMPERIAL_ARAMAIC = 186,
	BLOCK_CODE_OLD_SOUTH_ARABIAN = 187,
	BLOCK_CODE_AVESTAN = 188,
	BLOCK_CODE_INSCRIPTIONAL_PARTHIAN = 189,
	BLOCK_CODE_INSCRIPTIONAL_PAHLAVI = 190,
	BLOCK_CODE_OLD_TURKIC = 191,
	BLOCK_CODE_RUMI_NUMERAL_SYMBOLS = 192,
	BLOCK_CODE_KAITHI = 193,
	BLOCK_CODE_EGYPTIAN_HIEROGLYPHS = 194,
	BLOCK_CODE_ENCLOSED_ALPHANUMERIC_SUPPLEMENT = 195,
	BLOCK_CODE_ENCLOSED_IDEOGRAPHIC_SUPPLEMENT = 196,
	BLOCK_CODE_CJK_UNIFIED_IDEOGRAPHS_EXTENSION_C = 197,
	BLOCK_CODE_MANDAIC = 198,
	BLOCK_CODE_BATAK = 199,
	BLOCK_CODE_ETHIOPIC_EXTENDED_A = 200,
	BLOCK_CODE_BRAHMI = 201,
	BLOCK_CODE_BAMUM_SUPPLEMENT = 202,
	BLOCK_CODE_KANA_SUPPLEMENT = 203,
	BLOCK_CODE_PLAYING_CARDS = 204,
	BLOCK_CODE_MISCELLANEOUS_SYMBOLS_AND_PICTOGRAPHS = 205,
	BLOCK_CODE_EMOTICONS = 206,
	BLOCK_CODE_TRANSPORT_AND_MAP_SYMBOLS = 207,
	BLOCK_CODE_ALCHEMICAL_SYMBOLS = 208,
	BLOCK_CODE_CJK_UNIFIED_IDEOGRAPHS_EXTENSION_D = 209,
	BLOCK_CODE_ARABIC_EXTENDED_A = 210,
	BLOCK_CODE_ARABIC_MATHEMATICAL_ALPHABETIC_SYMBOLS = 211,
	BLOCK_CODE_CHAKMA = 212,
	BLOCK_CODE_MEETEI_MAYEK_EXTENSIONS = 213,
	BLOCK_CODE_MEROITIC_CURSIVE = 214,
	BLOCK_CODE_MEROITIC_HIEROGLYPHS = 215,
	BLOCK_CODE_MIAO = 216,
	BLOCK_CODE_SHARADA = 217,
	BLOCK_CODE_SORA_SOMPENG = 218,
	BLOCK_CODE_SUNDANESE_SUPPLEMENT = 219,
	BLOCK_CODE_TAKRI = 220,
	BLOCK_CODE_COUNT = 221,
	BLOCK_CODE_INVALID_CODE = -1,
	BPT_NONE = 0,
	BPT_OPEN = 1,
	BPT_CLOSE = 2,
	BPT_COUNT = 3,
	EA_NEUTRAL = 0,
	EA_AMBIGUOUS = 1,
	EA_HALFWIDTH = 2,
	EA_FULLWIDTH = 3,
	EA_NARROW = 4,
	EA_WIDE = 5,
	EA_COUNT = 6,
	UNICODE_CHAR_NAME = 0,
	UNICODE_10_CHAR_NAME = 1,
	EXTENDED_CHAR_NAME = 2,
	CHAR_NAME_ALIAS = 3,
	CHAR_NAME_CHOICE_COUNT = 4,
	SHORT_PROPERTY_NAME = 0,
	LONG_PROPERTY_NAME = 1,
	PROPERTY_NAME_CHOICE_COUNT = 2,
	DT_NONE = 0,
	DT_CANONICAL = 1,
	DT_COMPAT = 2,
	DT_CIRCLE = 3,
	DT_FINAL = 4,
	DT_FONT = 5,
	DT_FRACTION = 6,
	DT_INITIAL = 7,
	DT_ISOLATED = 8,
	DT_MEDIAL = 9,
	DT_NARROW = 10,
	DT_NOBREAK = 11,
	DT_SMALL = 12,
	DT_SQUARE = 13,
	DT_SUB = 14,
	DT_SUPER = 15,
	DT_VERTICAL = 16,
	DT_WIDE = 17,
	DT_COUNT = 18,
	JT_NON_JOINING = 0,
	JT_JOIN_CAUSING = 1,
	JT_DUAL_JOINING = 2,
	JT_LEFT_JOINING = 3,
	JT_RIGHT_JOINING = 4,
	JT_TRANSPARENT = 5,
	JT_COUNT = 6,
	JG_NO_JOINING_GROUP = 0,
	JG_AIN = 1,
	JG_ALAPH = 2,
	JG_ALEF = 3,
	JG_BEH = 4,
	JG_BETH = 5,
	JG_DAL = 6,
	JG_DALATH_RISH = 7,
	JG_E = 8,
	JG_FEH = 9,
	JG_FINAL_SEMKATH = 10,
	JG_GAF = 11,
	JG_GAMAL = 12,
	JG_HAH = 13,
	JG_TEH_MARBUTA_GOAL = 14,
	JG_HAMZA_ON_HEH_GOAL = 14,
	JG_HE = 15,
	JG_HEH = 16,
	JG_HEH_GOAL = 17,
	JG_HETH = 18,
	JG_KAF = 19,
	JG_KAPH = 20,
	JG_KNOTTED_HEH = 21,
	JG_LAM = 22,
	JG_LAMADH = 23,
	JG_MEEM = 24,
	JG_MIM = 25,
	JG_NOON = 26,
	JG_NUN = 27,
	JG_PE = 28,
	JG_QAF = 29,
	JG_QAPH = 30,
	JG_REH = 31,
	JG_REVERSED_PE = 32,
	JG_SAD = 33,
	JG_SADHE = 34,
	JG_SEEN = 35,
	JG_SEMKATH = 36,
	JG_SHIN = 37,
	JG_SWASH_KAF = 38,
	JG_SYRIAC_WAW = 39,
	JG_TAH = 40,
	JG_TAW = 41,
	JG_TEH_MARBUTA = 42,
	JG_TETH = 43,
	JG_WAW = 44,
	JG_YEH = 45,
	JG_YEH_BARREE = 46,
	JG_YEH_WITH_TAIL = 47,
	JG_YUDH = 48,
	JG_YUDH_HE = 49,
	JG_ZAIN = 50,
	JG_FE = 51,
	JG_KHAPH = 52,
	JG_ZHAIN = 53,
	JG_BURUSHASKI_YEH_BARREE = 54,
	JG_FARSI_YEH = 55,
	JG_NYA = 56,
	JG_ROHINGYA_YEH = 57,
	JG_COUNT = 58,
	GCB_OTHER = 0,
	GCB_CONTROL = 1,
	GCB_CR = 2,
	GCB_EXTEND = 3,
	GCB_L = 4,
	GCB_LF = 5,
	GCB_LV = 6,
	GCB_LVT = 7,
	GCB_T = 8,
	GCB_V = 9,
	GCB_SPACING_MARK = 10,
	GCB_PREPEND = 11,
	GCB_REGIONAL_INDICATOR = 12,
	GCB_COUNT = 13,
	WB_OTHER = 0,
	WB_ALETTER = 1,
	WB_FORMAT = 2,
	WB_KATAKANA = 3,
	WB_MIDLETTER = 4,
	WB_MIDNUM = 5,
	WB_NUMERIC = 6,
	WB_EXTENDNUMLET = 7,
	WB_CR = 8,
	WB_EXTEND = 9,
	WB_LF = 10,
	WB_MIDNUMLET = 11,
	WB_NEWLINE = 12,
	WB_REGIONAL_INDICATOR = 13,
	WB_HEBREW_LETTER = 14,
	WB_SINGLE_QUOTE = 15,
	WB_DOUBLE_QUOTE = 16,
	WB_COUNT = 17,
	SB_OTHER = 0,
	SB_ATERM = 1,
	SB_CLOSE = 2,
	SB_FORMAT = 3,
	SB_LOWER = 4,
	SB_NUMERIC = 5,
	SB_OLETTER = 6,
	SB_SEP = 7,
	SB_SP = 8,
	SB_STERM = 9,
	SB_UPPER = 10,
	SB_CR = 11,
	SB_EXTEND = 12,
	SB_LF = 13,
	SB_SCONTINUE = 14,
	SB_COUNT = 15,
	LB_UNKNOWN = 0,
	LB_AMBIGUOUS = 1,
	LB_ALPHABETIC = 2,
	LB_BREAK_BOTH = 3,
	LB_BREAK_AFTER = 4,
	LB_BREAK_BEFORE = 5,
	LB_MANDATORY_BREAK = 6,
	LB_CONTINGENT_BREAK = 7,
	LB_CLOSE_PUNCTUATION = 8,
	LB_COMBINING_MARK = 9,
	LB_CARRIAGE_RETURN = 10,
	LB_EXCLAMATION = 11,
	LB_GLUE = 12,
	LB_HYPHEN = 13,
	LB_IDEOGRAPHIC = 14,
	LB_INSEPARABLE = 15,
	LB_INSEPERABLE = 15,
	LB_INFIX_NUMERIC = 16,
	LB_LINE_FEED = 17,
	LB_NONSTARTER = 18,
	LB_NUMERIC = 19,
	LB_OPEN_PUNCTUATION = 20,
	LB_POSTFIX_NUMERIC = 21,
	LB_PREFIX_NUMERIC = 22,
	LB_QUOTATION = 23,
	LB_COMPLEX_CONTEXT = 24,
	LB_SURROGATE = 25,
	LB_SPACE = 26,
	LB_BREAK_SYMBOLS = 27,
	LB_ZWSPACE = 28,
	LB_NEXT_LINE = 29,
	LB_WORD_JOINER = 30,
	LB_H2 = 31,
	LB_H3 = 32,
	LB_JL = 33,
	LB_JT = 34,
	LB_JV = 35,
	LB_CLOSE_PARENTHESIS = 36,
	LB_CONDITIONAL_JAPANESE_STARTER = 37,
	LB_HEBREW_LETTER = 38,
	LB_REGIONAL_INDICATOR = 39,
	LB_COUNT = 40,
	NT_NONE = 0,
	NT_DECIMAL = 1,
	NT_DIGIT = 2,
	NT_NUMERIC = 3,
	NT_COUNT = 4,
	HST_NOT_APPLICABLE = 0,
	HST_LEADING_JAMO = 1,
	HST_VOWEL_JAMO = 2,
	HST_TRAILING_JAMO = 3,
	HST_LV_SYLLABLE = 4,
	HST_LVT_SYLLABLE = 5,
	HST_COUNT = 6;

public static /*. int[int] .*/ function charAge(/*. mixed .*/ $codepoint){}
public static /*. int .*/ function charDigitValue(/*. mixed .*/ $codepoint){}
public static /*. int .*/ function charDirection(/*. mixed .*/ $codepoint){}
public static /*. int .*/ function charFromName(/*. string .*/ $characterName, $nameChoice = self::UNICODE_CHAR_NAME){}
public static /*. mixed .*/ function charMirror(/*. mixed .*/ $codepoint){}
public static /*. string .*/ function charName(/*. mixed .*/ $codepoint, $nameChoice = self::UNICODE_CHAR_NAME){}
public static /*. int .*/ function charType(/*. mixed .*/ $codepoint){}
public static /*. string .*/ function chr(/*. mixed .*/ $codepoint){}
public static /*. int .*/ function digit(/*. string .*/ $codepoint, $radix = 10){}
public static /*. void .*/ function enumCharNames(/*. mixed .*/ $start, /*. mixed .*/ $limit, /*. mixed .*/ $callback, $nameChoice = self::UNICODE_CHAR_NAME){}
public static /*. void .*/ function enumCharTypes(/*. mixed .*/ $callback){}
// FIXME: constant self::FOLD_CASE_DEFAULT not defined (bug #71351):
//public static /*. mixed .*/ function foldCase(/*. mixed .*/ $codepoint, $options = self::FOLD_CASE_DEFAULT){}
public static /*. int .*/ function forDigit(/*. int .*/ $digit, $radix = 10){}
public static /*. mixed .*/ function getBidiPairedBracket(/*. mixed .*/ $codepoint){}
public static /*. int .*/ function getBlockCode(/*. mixed .*/ $codepoint){}
public static /*. int .*/ function getCombiningClass(/*. mixed .*/ $codepoint){}
public static /*. string .*/ function getFC_NFKC_Closure(/*. mixed .*/ $codepoint){}
public static /*. int .*/ function getIntPropertyMaxValue(/*. int .*/ $property){}
public static /*. int .*/ function getIntPropertyMinValue(/*. int .*/ $property){}
public static /*. int .*/ function getIntPropertyValue(/*. mixed .*/ $codepoint, /*. int .*/ $property){}
public static /*. float .*/ function getNumericValue(/*. mixed .*/ $codepoint){}
public static /*. int .*/ function getPropertyEnum(/*. string .*/ $alias){}
public static /*. string .*/ function getPropertyName(/*. int .*/ $property, $nameChoice = self::LONG_PROPERTY_NAME){}
public static /*. int .*/ function getPropertyValueEnum(/*. int .*/ $property, /*. string .*/ $name){}
public static /*. string .*/ function getPropertyValueName(/*. int .*/ $property, /*. int .*/ $value, $nameChoice = self::LONG_PROPERTY_NAME){}
public static /*. int[int] .*/ function getUnicodeVersion(){}
public static /*. bool .*/ function hasBinaryProperty(/*. mixed .*/ $codepoint, /*. int .*/ $property){}
public static /*. bool .*/ function isalnum(/*. mixed .*/ $codepoint){}
public static /*. bool .*/ function isalpha(/*. mixed .*/ $codepoint){}
public static /*. bool .*/ function isbase(/*. mixed .*/ $codepoint){}
public static /*. bool .*/ function isblank(/*. mixed .*/ $codepoint){}
public static /*. bool .*/ function iscntrl(/*. mixed .*/ $codepoint){}
public static /*. bool .*/ function isdefined(/*. mixed .*/ $codepoint){}
public static /*. bool .*/ function isdigit(/*. mixed .*/ $codepoint){}
public static /*. bool .*/ function isgraph(/*. mixed .*/ $codepoint){}
public static /*. bool .*/ function isIDIgnorable(/*. mixed .*/ $codepoint){}
public static /*. bool .*/ function isIDPart(/*. mixed .*/ $codepoint){}
public static /*. bool .*/ function isIDStart(/*. mixed .*/ $codepoint){}
public static /*. bool .*/ function isISOControl(/*. mixed .*/ $codepoint){}
public static /*. bool .*/ function isJavaIDPart(/*. mixed .*/ $codepoint){}
public static /*. bool .*/ function isJavaIDStart(/*. mixed .*/ $codepoint){}
public static /*. bool .*/ function isJavaSpaceChar(/*. mixed .*/ $codepoint){}
public static /*. bool .*/ function islower(/*. mixed .*/ $codepoint){}
public static /*. bool .*/ function isMirrored(/*. mixed .*/ $codepoint){}
public static /*. bool .*/ function isprint(/*. mixed .*/ $codepoint){}
public static /*. bool .*/ function ispunct(/*. mixed .*/ $codepoint){}
public static /*. bool .*/ function isspace(/*. mixed .*/ $codepoint){}
public static /*. bool .*/ function istitle(/*. mixed .*/ $codepoint){}
public static /*. bool .*/ function isUAlphabetic(/*. mixed .*/ $codepoint){}
public static /*. bool .*/ function isULowercase(/*. mixed .*/ $codepoint){}
public static /*. bool .*/ function isupper(/*. mixed .*/ $codepoint){}
public static /*. bool .*/ function isUUppercase(/*. mixed .*/ $codepoint){}
public static /*. bool .*/ function isUWhiteSpace(/*. mixed .*/ $codepoint){}
public static /*. bool .*/ function isWhitespace(/*. mixed .*/ $codepoint){}
public static /*. bool .*/ function isxdigit(/*. mixed .*/ $codepoint){}
public static /*. int .*/ function ord(/*. mixed .*/ $character){}
public static /*. mixed .*/ function tolower(/*. mixed .*/ $codepoint){}
public static /*. mixed .*/ function totitle(/*. mixed .*/ $codepoint){}
public static /*. mixed .*/ function toupper(/*. mixed .*/ $codepoint){}
}
/*. end_if_php_ver .*/
