<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<head>
    <meta charset="UTF-8" />
    <title><?php echo $view['slots']->get('pageTitle', 'Mautic'); ?></title>
    <link rel="icon" type="image/x-icon" href="<?php echo $view['assets']->getUrl('media/images/favicon.ico') ?>" />
    <link rel="apple-touch-icon" href="<?php echo $view['assets']->getUrl('media/images/apple-touch-icon.png') ?>" />

    <?php
    foreach ($view['assetic']->stylesheets(array('@mautic_stylesheets'), array(), array('combine' => true, 'output' => 'media/css/mautic.css')) as $url): ?>
        <link rel="stylesheet" href="<?php echo $view->escape($url) ?>" />
    <?php endforeach; ?>
    <link rel="stylesheet" href="<?php echo $view['assets']->getUrl('media/font-awesome/css/font-awesome.min.css'); ?>" />
</head>