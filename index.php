<?php 
/**
 * The index page
 *
 * @link https://github.com/vonKrafft/Wink/blob/master/index.php
 *
 * @package Wink
 */

require_once('config.php'); ?><!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title><?php echo SITE_TITLE; ?></title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>

    <header class="page-heading">
        <div class="heading">
            <a href="<?php echo base_url(); ?>"><h1 class="page-title"><?php echo SITE_TITLE; ?></h1></a>
            <form method="GET" action="<?php echo base_url(); ?>">
                <?php if (is_logged_user()) : ?><input type="hidden" id="apikey" name="apikey" value="<?php echo xss_safe($_GET['apikey']); ?>"><?php endif; ?>
                <input type="text" id="s" name="s" placeholder="Search ..." value="<?php echo xss_safe($_GET['s']); ?>">
            </form>
        </div>
    </header>

    <?php if (isset($alert)) : ?>
    <div class="alert alert-<?php echo $alert['status']; ?>">
        <b><?php echo $alert['status']; ?>!</b> <?php echo $alert['message']; ?>
    </div>
    <?php endif; ?>

    <?php if (is_logged_user()) : ?>
    <div class="post">
        <form method="POST" action="<?php echo base_url(); ?>">
            <textarea id="content" name="content" placeholder="URL and short description ..."></textarea>
            <footer>
                <input type="text" name="user" disabled="disabled" value="Logged as <?php echo get_logged_user(); ?>">
                <input type="submit" value="Post">
            </footer>
        </form>
    </div>
    <?php endif; ?>

    <div class="clearfix">
        <div class="stats stats-left">
            <h3>This month</h3>
            <table>
                <tbody>
                    <?php $i = 1; foreach (get_author_list(1) as $author => $counter) : ?>
                        <tr>
                            <td><b>#<?php echo $i; ?></b></td>
                            <td><a href="<?php echo base_url(array('author' => $author, 'date' => date('Y-m'))); ?>"><?php echo xss_safe($author); ?></a></td>
                            <td><?php echo $counter; ?></td>
                        </tr>
                    <?php $i++; endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="stats">
            <h3>Last month</h3>
            <table>
                <tbody>
                    <?php $i = 1; foreach (get_author_list(0) as $author => $counter) : ?>
                        <tr>
                            <td><b>#<?php echo $i; ?></b></td>
                            <td><a href="<?php echo base_url(array('author' => $author, 'date' => date('Y-m', strtotime('first day of previous month')))); ?>"><?php echo xss_safe($author); ?></a></td>
                            <td><?php echo $counter; ?></td>
                        </tr>
                    <?php $i++; endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="stats stats-right">
            <h3>Main rank</h3>
            <table>
                <tbody>
                    <?php $i = 1; foreach (get_author_list(-1) as $author => $counter) : ?>
                        <tr>
                            <td><b>#<?php echo $i; ?></b></td>
                            <td><a href="<?php echo base_url(array('author' => $author, 'date' => NULL)); ?>"><?php echo xss_safe($author); ?></a></td>
                            <td><?php echo $counter; ?></td>
                        </tr>
                    <?php $i++; endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php
        $limit = POSTS_PER_PAGE;
        $offset = array_key_exists('page', $_GET) ? POSTS_PER_PAGE * (intval($_GET['page']) - 1) : 0;
        if (array_key_exists('date', $_GET) or array_key_exists('author', $_GET) or array_key_exists('tag', $_GET))
        {
            $where = array();
            if (array_key_exists('date', $_GET) and ! empty($_GET['date'])) $where['date'] = $_GET['date'];
            if (array_key_exists('author', $_GET) and ! empty($_GET['author'])) $where['author'] = $_GET['author'];
            if (array_key_exists('tag', $_GET) and ! empty($_GET['tag'])) $where['content'] = $_GET['tag'];
            list($posts, $total_posts) = empty($where) ? find_all($limit, $offset) : find_by_and($where, $limit, $offset);
        }
        elseif (array_key_exists('s', $_GET)) {
            $where = array(
                'content'     => $_GET['s'],
                'title'       => $_GET['s'],
                'description' => $_GET['s'],
            );
            list($posts, $total_posts) = find_by_or($where, $limit, $offset);
        }
        else
        {
            list($posts, $total_posts) = find_all($limit, $offset);
        }
    ?>

    <?php if (array_key_exists('s', $_GET)) : ?>
    <div class="post">
        <div class="content">
            <a href="<?php echo base_url(); ?>" class="btn pull-right">&times; Erase filters</a>
            <span><?php echo $total_posts; ?> result<?php echo ($total_posts > 1) ? 's' : ''; ?> for "<?php echo xss_safe($_GET['s']); ?>"</span>
        </div>
    </div>
    <?php elseif (array_key_exists('tag', $_GET)) : ?>
    <div class="post">
        <div class="content">
            <a href="<?php echo base_url(); ?>" class="btn pull-right">&times; Erase filters</a>
            <span><?php echo $total_posts; ?> result<?php echo ($total_posts > 1) ? 's' : ''; ?> for #<?php echo xss_safe($_GET['tag']); ?></span>
        </div>
    </div>
    <?php elseif (array_key_exists('date', $_GET) and array_key_exists('author', $_GET)) : ?>
    <div class="post">
        <div class="content">
            <a href="<?php echo base_url(); ?>" class="btn pull-right">&times; Erase filters</a>
            <span><?php echo $total_posts; ?> link<?php echo ($total_posts > 1) ? 's' : ''; ?> posted on <?php echo xss_safe($_GET['date']); ?> by <?php echo xss_safe($_GET['author']); ?></span>
        </div>
    </div>
    <?php elseif (array_key_exists('date', $_GET)) : ?>
    <div class="post">
        <div class="content">
            <a href="<?php echo base_url(); ?>" class="btn pull-right">&times; Erase filters</a>
            <span><?php echo $total_posts; ?> link<?php echo ($total_posts > 1) ? 's' : ''; ?> posted on <?php echo xss_safe($_GET['date']); ?></span>
        </div>
    </div>
    <?php elseif (array_key_exists('author', $_GET)) : ?>
    <div class="post">
        <div class="content">
            <a href="<?php echo base_url(); ?>" class="btn pull-right">&times; Erase filters</a>
            <span><?php echo $total_posts; ?> link<?php echo ($total_posts > 1) ? 's' : ''; ?> posted by <?php echo xss_safe($_GET['author']); ?></span>
        </div>
    </div>
    <?php else : ?>
    <div class="post">
        <div class="content">
            <span><?php echo $total_posts; ?> link<?php echo ($total_posts > 1) ? 's' : ''; ?></span>
        </div>
    </div>
    <?php endif; ?>

    <?php foreach ($posts as $post) : ?>
    <div class="post">
        <div class="content">
            <span>
                Posted on <a href="<?php echo base_url(array('date' => strftime('%Y-%m-%d', strtotime($post->date)))); ?>"><?php echo strftime('%Y-%m-%d', strtotime($post->date)); ?></a>
                by <a href="<?php echo base_url(array('author' => $post->author)); ?>"><?php echo xss_safe($post->author); ?></a>
            </span>
            <?php $post->content = xss_safe($post->content); ?>
            <?php foreach ($post->hashtags as $tag) $post->content = str_replace('#' . $tag, '<a class="hashtag" href="' . base_url(array('tag' => $tag)) . '">#' . $tag . '</a>', $post->content); ?>
            <p><?php echo $post->content; ?></p>
        </div>
        <a class="link" href="<?php echo xss_safe($post->url); ?>" target="_blank">
        <?php if ($post->image !== NULL): ?>
            <div class="link-image">
                <img src="<?php echo $post->image; ?>">
            </div>
            <div class="link-text">
                <h3 class="link-title"><?php echo xss_safe(strlen($post->title) > 0 ? $post->title : $post->url); ?></h3>
                <p class="link-description"><?php echo character_limiter($post->description, 150); ?></p>
                <p class="link-host"><?php echo xss_safe($post->host); ?></p>
            </div>
        <?php else : ?>
            <div class="link-text full">
                <h3 class="link-title"><?php echo xss_safe(strlen($post->title) > 0 ? $post->title : $post->url); ?></h3>
                <p class="link-description"><?php echo character_limiter($post->description, 150); ?></p>
                <p class="link-host"><?php echo xss_safe($post->host); ?></p>
            </div>
        <?php endif; ?>
        </a>
    </div>
    <?php endforeach; ?>

    <nav aria-label="Page navigation">
        <ul class="pagination">
            <li>
                <a href="<?php echo  base_url(); ?>" aria-label="First">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>
            <?php
            for ($page=1; $page <= max(1, ceil($total_posts / POSTS_PER_PAGE)); $page++) 
            {
                echo ($current_page == $page) ? '<li class="active">' : '<li>';
                echo '<a href="' . base_url(array('page' => $page)) .'">' . $page . '</a></li>';
            }
            ?>
            <li>
                <a href="<?php echo base_url(array('page' => ceil($total_posts / POSTS_PER_PAGE))); ?>" aria-label="Last">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        </ul>
    </nav>

    <footer class="page-footer"><a href="https://github.com/vonKrafft/Wink">Wink</a> - The personal, minimalist, no-database web tools for centralizing URL.</footer>

</body>
</html>