<?php
/**
 * Wink functions and definitions
 *
 * @link https://github.com/vonKrafft/Wink/blob/master/app.php
 *
 * @package Wink
 * @since 1.0
 */

/**
 * Initialize the application.
 *
 * @since 1.0
 *
 * @global mixed[] $wkdb  The database.
 * @return string  $alert An alert message in case of data processing to inform about a failure or a success.
 */
function init()
{
    global $wkdb;
    $alert = NULL;
    # Load database
    $wkdb = load_database();
    # Process new post
    if (strtoupper($_SERVER['REQUEST_METHOD']) === 'POST')
    {
        # Data
        $apikey = get_parameter('apikey');
        $content = get_parameter('content');
        $author = get_logged_author($apikey, get_parameter('author'));
        # If the key is valid
        if ($author !== FALSE)
        {
            $alert = process_post($content, $author);
            # Reload database
            $wkdb = load_database();
        }
        elseif (is_master_key($apikey))
        {
            $alert = array(
                'status'  => 'error',
                'message' => 'A username must be provided with the master key.',
            );
        }
        else
        {
            $alert = array(
                'status'  => 'error',
                'message' => 'Invalid secret key!',
            );
        }
    }
    return $alert;
}

/**
 * Search parameter in POST and GET variables
 *
 * @since 1.1
 *
 * @param  string $key   The wanted key.
 * @return mixed  $param The requested parameter.
 */
function get_parameter( $key )
{
    $params = array_merge($_POST, $_GET);
    return array_key_exists($key, $params) ? $params[$key] : NULL;
}

/**
 * Load the database into a PHP variable.
 *
 * @since 1.0
 *
 * @return mixed[] $database The database.
 */
function load_database()
{
    $database = array();
    foreach (glob(DATA_DIR . '/*.json') as $filename)
    {
        $database[basename($filename)] = json_decode(file_get_contents($filename));
    }
    return $database;
}

/**
 * Parse the published message and saves the data in a JSON file.
 *
 * @since 1.0
 *
 * @param  string      $content  The published message.
 * @param  string|NULL $author   The author (optionnal).
 * @return mixed[]     $alert    An alert message to inform about a failure or a success.
 */
function process_post( $content, $author = NULL )
{
    $data = array(
        'date'        => date("Y-m-d H:i:s"),
        'url'         => NULL,
        'content'     => htmlentities($content),
        'title'       => NULL,
        'description' => NULL,
        'host'        => NULL,
        'hashtags'    => array(),
        'author'      => isset($author) ? $author : get_logged_user(),
        'image'       => NULL,
    );

    # Check if there is content
    if (empty($data['content']))
    {
        return array(
            'status'  => 'warning',
            'message' => 'Message cannot be empty!',
        );
    }
    else
    {
        # Check URL
        if (preg_match('/\b((https?:\/\/?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|\/)))/i', $data['content'], $matches))
        {
            $data['url'] = $matches[1];
            $data['content'] = str_replace($matches[1], '', $data['content']);
            $data['host'] = strstr(preg_replace('/(https?|ftp):\/\//', '', $data['url']), '/', TRUE);

            # If the link has already been shared
            if (link_exist($data['url']) !== FALSE)
            {
                return array(
                    'status'  => 'info',
                    'message' => 'The link you want to share has already been added by ' . link_exist($data['url']) . '.',
                );
            }

            # Get the HTML page
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $data['url']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
            $post_html = curl_exec($ch);
            curl_close($ch); 

            # Metadata
            $meta_properties = array();
            if (preg_match('/<title[^>]*>([\s\S]*)<\/title>/i', $post_html, $matches))
            {
                $meta_properties['title'] = $matches[1];
            }
            preg_match_all('/<meta[^>]*>/i', $post_html, $meta, PREG_SET_ORDER);
            foreach ($meta as $m)
            {
                if (preg_match('/property=["\'](?:og|article|twitter):([^"\']*)["\']/', $m[0], $property))
                {
                    if (preg_match('/content=["\']([^"\']*)["\']/', $m[0], $content))
                    {
                        $meta_properties[$property[1]] = $content[1];
                    }
                }
                elseif (preg_match('/name=["\'](description)["\']/', $m[0], $property))
                {
                    if (preg_match('/content=["\']([^"\']*)["\']/', $m[0], $content))
                    {
                        $meta_properties[$property[1]] = $content[1];
                    }
                }
            }
            $data['title'] = array_key_exists('title', $meta_properties) ? $meta_properties['title'] : NULL;
            $data['description'] = array_key_exists('description', $meta_properties) ? $meta_properties['description'] : NULL;
            $data['image'] = array_key_exists('image', $meta_properties) ? $meta_properties['image'] : NULL;

            # Download cover image
            if ($data['image'] !== NULL and is_image_url($data['image']))
            {
                $type = pathinfo($data['image'], PATHINFO_EXTENSION);
                $image = file_get_contents($data['image'], FALSE, NULL, 0, 4096*1024); // if the file is over 4MB, drop it
                $data['image'] = 'data:image/' . $type . ';base64,' . base64_encode($image);
            }

            # Hashtags
            if (preg_match_all('/\#([\w]+)/i', $data['content'], $tags, PREG_SET_ORDER))
            {
                foreach ($tags as $tag)
                {
                    $data['hashtags'][] = $tag[1];
                }
            }

            # Save post
            $filename = strftime('%Y%m%d%H%M%S', strtotime($data['date'])) . '-' . md5($data['url']) . '.json';
            if (file_put_contents(DATA_DIR . '/' . $filename, json_encode($data, JSON_PRETTY_PRINT)) === FALSE)
            {
                return array(
                    'status'  => 'error',
                    'message' => 'Unable to save data!',
                );
            }
        }
        else
        {
            return array(
                'status'  => 'error',
                'message' => 'You must provide a link!',
            );
        }
    }
    return array(
        'status'  => 'success',
        'message' => 'The link and its description have been saved.',
    );
}

/**
 * Get the associated author thanks to a secret key
 *
 * @since 1.1
 *
 * @global mixed[]      $apikeys  The list of secret keys.
 * @param  string       $key      The wanted key.
 * @param  string|FALSE $author   An optionnal author's name if the secret key is a master key.
 * @return string|FALSE $username The name of the user if he is logged in, FALSE otherwise.
 */
function get_logged_author( $key, $author = FALSE )
{
    global $apikeys;
    $author = (is_string($author) and ! empty($author)) ? $author : FALSE;
    if (array_key_exists($key, $apikeys))
    {
        return ($apikeys[$key] !== TRUE) ? $apikeys[$key] : $author;
    }
    return FALSE;
}

/**
 * Check if the given key is a master key
 *
 * @since 1.1
 *
 * @global mixed[]      $apikeys  The list of secret keys.
 * @param  string       $key      The wanted key.
 * @return string|FALSE $username TRUE if it is a master key, FALSE otherwise.
 */
function is_master_key( $key )
{
    global $apikeys;
    return array_key_exists($key, $apikeys) ? ($apikeys[$key] === TRUE) : FALSE;
}

/**
 * Check if the user is logged in.
 *
 * @since 1.0
 *
 * @return bool $is_logged_user TRUE if the user is logged in, FALSE otherwise.
 */
function is_logged_user()
{
    return (get_logged_user() !== FALSE);
}

/**
 * Get the name of the logged in user.
 *
 * @since 1.0
 *
 * @global mixed[]      $apikeys  The list of secret keys.
 * @return string|FALSE $username The name of the user if he is logged in, FALSE otherwise.
 */
function get_logged_user()
{
    global $apikeys;
    if (array_key_exists('apikey', $_GET))
    {
        return array_key_exists($_GET['apikey'], $apikeys) ? $apikeys[$_GET['apikey']] : FALSE;
    }
    return FALSE;
}

/**
 * Get the apikey of the logged in user.
 *
 * @since 1.1
 *
 * @global mixed[]      $apikeys The list of secret keys.
 * @return string|FALSE $apikey  The secret key of the user if he is logged in, FALSE otherwise.
 */
function get_logged_apikey()
{
    global $apikeys;
    if (array_key_exists('apikey', $_POST))
    {
        return array_key_exists($_POST['apikey'], $apikeys) ? $_POST['apikey'] : FALSE;
    }
    return FALSE;
}

/**
 * Check if a link exists in the database.
 *
 * @since 1.0
 *
 * @global mixed[]      $wkdb     The database.
 * @param  string       $url      The link to look for.
 * @return string|FALSE $username The name of the original author if the link exists, FALSE otherwise.
 */
function link_exist( $url )
{
    global $wkdb;
    foreach ($wkdb as $key => $value)
    {
        if (strstr($value->url, $url) !== FALSE)
        {
            return $value->author;
        }
    }
    return FALSE;
}

/**
 * Sort an array according to a specific key.
 *
 * @since 1.0
 *
 * @param  string $key       The key used for sorting.
 * @param  string $way       The sorting direction (DESC or ASC).
 * @return int    $strnatcmp The comparison result.
 */
function order_by( $key, $way ) {
    return function ($a, $b) use ($key, $way) {
        return (strtoupper($way) === 'DESC') ? -strnatcmp($a->$key, $b->$key) : strnatcmp($a->$key, $b->$key);
    };
}

/**
 * Find all the posts of a page, without condition.
 *
 * @since 1.0
 *
 * @global mixed[]  $wkdb     The database.
 * @param  int      $limit    The number of items desired (default: 10).
 * @param  int      $offset   The offset for slicing (default: 0).
 * @param  string[] $order_by The sorting criteria (default: array('date', 'DESC')).
 * @return mixed[]  $data     The posts of a page
 */
function find_all($limit = 10, $offset = 0, $order_by = array('date', 'DESC'))
{
    global $wkdb;
    # Sorting and slicing
    list($key, $way) = $order_by;
    usort($wkdb, order_by($key, $way));
    return array(array_slice($wkdb, $offset, $limit), count($wkdb));
}

/**
 * Find all the posts of a page, with a "AND" condition.
 *
 * @since 1.0
 *
 * @global mixed[]  $wkdb          The database.
 * @param  mixed[]  $limit         The where condition.
 * @param  int      $limit         The number of items desired (default: 10).
 * @param  int      $offset        The offset for slicing (default: 0).
 * @param  string[] $order_by      The sorting criteria (default: array('date', 'DESC')).
 * @return mixed[]  $filtered_data The posts of a page
 */
function find_by_and($where, $limit = 10, $offset = 0, $order_by = array('date', 'DESC'))
{
    global $wkdb;
    $filtered_data = array();
    foreach ($wkdb as $key => $value)
    {
        # Without evidence to the contrary, it is assumed that the item matches the search
        $is_match = TRUE;
        foreach ($where as $column => $search)
        {
            # If one of the conditions is asserted, then the item does not match to the search
            if (strstr(strtolower($value->$column), strtolower($search)) === FALSE) {
                $is_match = FALSE;
            }
        }
        # If the item matches to the search, we add it to the list of results
        if ($is_match)
        {
            $filtered_data[$key] = $value;
        }
    }
    # Sorting and slicing
    list($key, $way) = $order_by;
    usort($filtered_data, order_by($key, $way));
    return array(array_slice($filtered_data, $offset, $limit), count($filtered_data));
}

/**
 * Find all the posts of a page, with a "OR" condition.
 *
 * @since 1.0
 *
 * @global mixed[]  $wkdb          The database.
 * @param  mixed[]  $limit         The where condition.
 * @param  int      $limit         The number of items desired (default: 10).
 * @param  int      $offset        The offset for slicing (default: 0).
 * @param  string[] $order_by      The sorting criteria (default: array('date', 'DESC')).
 * @return mixed[]  $filtered_data The posts of a page
 */
function find_by_or($where, $limit = 10, $offset = 0, $order_by = array('date', 'DESC'))
{
    global $wkdb;
    $filtered_data = array();
    foreach ($wkdb as $key => $value)
    {
        # Without evidence to the contrary, it is assumed that the item does not match the search
        $is_match = FALSE;
        foreach ($where as $column => $search)
        {
            # If one of the conditions is asserted, then the item matches to the search
            if (strstr(strtolower($value->$column), strtolower($search)) !== FALSE) {
                $is_match = TRUE;
            }
        }
        # If the item matches to the search, we add it to the list of results
        if ($is_match)
        {
            $filtered_data[$key] = $value;
        }
    }
    # Sorting and slicing
    list($key, $way) = $order_by;
    usort($filtered_data, order_by($key, $way));
    return array(array_slice($filtered_data, $offset, $limit), count($filtered_data));
}

/**
 * Retrieve the rank of the authors.
 *
 * @since 1.0
 *
 * @global mixed[] $wkdb    The database.
 * @param  int     $month   The time period, 1 for the current month, 0 for the previous month and -1 for the overall ranking. (default: 0).
 * @return mixed[] $authors The top five of publishers
 */
function get_author_list( $month = 0 )
{
    global $wkdb;
    $authors = array();
    foreach ($wkdb as $key => $value)
    {
        $current_month = strtotime('last day of previous month');
        $previous_month = strtotime('first day of previous month');
        $post_date = strtotime($value->date);
        if (($month === -1) or ($month === 0 and $post_date <= $current_month and $post_date >= $previous_month) or ($month === 1 and $post_date > $current_month))
        if (array_key_exists($value->author, $authors))
        {
            $authors[$value->author]++;
        }
        else
        {
            $authors[$value->author] = 1;
        }
    }
    arsort($authors);
    return array_slice($authors, 0, 5);
}

/**
 * Truncate a string.
 *
 * @since 1.0
 *
 * @param  string $string The string.
 * @param  int    $len    The maximum length desired.
 * @param  string $suffix The suffix to add to the string if it needs to be truncated (default: '...').
 * @return string $string The truncated string.
 */
function character_limiter( $string, $len, $suffix = '...' )
{
    if (strlen($string) <= $len)
    {
        return $string;
    }
    return (strpos($string, ' ', $len) === FALSE) ? $string : substr($string, 0, strpos($string, ' ', $len)) . $suffix;
}

/**
 * Get the URL of the application.
 *
 * @since 1.0
 *
 * @param  mixed[] $params   The GET parameter array to add to the URL.
 * @return string  $base_url The URL.
 */
function base_url( $params = array() )
{
    $url = BASE_URL;
    if (is_array($params) and ! empty($params))
    {
        $url .= '?';
        foreach (array_merge($_GET, $params) as $key => $value) {
            if ($key === 's' or empty($value)) continue;
            $url .= $key . '=' . urlencode($value) . '&';
        }
        $url = substr($url, 0, -1);
    }
    else
    {
        $url .= array_key_exists('apikey', $_GET) ? '?apikey=' . urlencode($_GET['apikey']) : '';
    }
    return $url;
}

/**
 * Filter a string to protect against XSS.
 *
 * @since 1.0
 *
 * @param  string $string The string.
 * @return string $string The filtered string.
 */
function xss_safe( $string )
{
    return htmlentities(html_entity_decode($string));
}

/**
 * Check if a URL matches an image.
 *
 * @since 1.2
 *
 * @param  string $url      The image's URL.
 * @return bool   $is_image TRUE if the URL matches an image, FALSE otherwise.
 */
function is_image_url( $url )
{
    if (preg_match('/\b((https?:\/\/?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|\/)))/i', $data['content'], $matches))
    {
        $imagesize = getimagesize($url);
        if(@is_array($imagesize))
        {
            return in_array($imagesize[2] , array(IMAGETYPE_GIF , IMAGETYPE_JPEG ,IMAGETYPE_PNG , IMAGETYPE_BMP));
        }
    }
    return FALSE;
}
