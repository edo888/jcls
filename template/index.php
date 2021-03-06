<?php
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $this->language; ?>" lang="<?php echo $this->language; ?>" >
<head>
<jdoc:include type="head" />
<link rel="stylesheet" href="<?php echo $this->baseurl ?>/templates/system/css/system.css" type="text/css" />
<link rel="stylesheet" href="<?php echo $this->baseurl ?>/templates/system/css/general.css" type="text/css" />
<link rel="stylesheet" href="<?php echo $this->baseurl ?>/templates/<?php echo $this->template ?>/css/template.css" type="text/css" />
<!--[if lte IE 6]>
<link href="<?php echo $this->baseurl ?>/templates/<?php echo $this->template ?>/css/ieonly.css" rel="stylesheet" type="text/css" />
<![endif]-->
</head>
<body>
<a name="up" id="up"></a>
<div id="page">
    <div id="header">
        <div id="header-top">
            <div id="header-left"><a href="<?php echo $this->baseurl ?>"><img border="0" alt="World Bank Group" src="<?php echo $this->baseurl ?>/templates/<?php echo $this->template ?>/images/logo.gif"/></a> <jdoc:include type="modules" name="top" /></div>
            <div id="header-right"><jdoc:include type="modules" name="top-right" /></div>
        </div>
        <div id="header-menu"><jdoc:include type="modules" name="top-menu" /></div>
        <div id="breadcrumb"><jdoc:include type="modules" name="breadcrumb" /></div>
    </div>
    <div id="content">
        <div id="main-content">
            <jdoc:include type="message" />
            <jdoc:include type="component" />
        </div>
        <div id="right"><jdoc:include type="modules" name="right" style="xhtml" /></div>
    </div>
    <div id="footer">
        <div id="footer-left"><jdoc:include type="modules" name="footer-left" /></div>
        <div id="footer-right"><jdoc:include type="modules" name="footer-right" /></div>
    </div>
</div>

<jdoc:include type="modules" name="debug" />

<?php
/*
<div class="center" align="center">
    <div id="wrapper">
        <div id="wrapper_r">
            <div id="header">
                <div id="header_l">
                    <div id="header_r">
                        <div id="logo"></div>
                        <jdoc:include type="modules" name="top" />
                    </div>
                </div>
            </div>

            <div id="tabarea">
                <div id="tabarea_l">
                    <div id="tabarea_r">
                        <div id="tabmenu">
                        <table cellpadding="0" cellspacing="0" class="pill">
                            <tr>
                                <td class="pill_l">&nbsp;</td>
                                <td class="pill_m">
                                <div id="pillmenu">
                                    <jdoc:include type="modules" name="user3" />
                                </div>
                                </td>
                                <td class="pill_r">&nbsp;</td>
                            </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div id="search">
                <jdoc:include type="modules" name="user4" />
            </div>

            <div id="pathway">
                <jdoc:include type="modules" name="breadcrumb" />
            </div>

            <div class="clr"></div>

            <div id="whitebox">
                <div id="whitebox_t">
                    <div id="whitebox_tl">
                        <div id="whitebox_tr"></div>
                    </div>
                </div>

                <div id="whitebox_m">
                    <div id="area">
                                    <jdoc:include type="message" />

                        <div id="leftcolumn">
                        <?php if($this->countModules('left')) : ?>
                            <jdoc:include type="modules" name="left" style="rounded" />
                        <?php endif; ?>
                        </div>

                        <?php if($this->countModules('left')) : ?>
                        <div id="maincolumn">
                        <?php else: ?>
                        <div id="maincolumn_full">
                        <?php endif; ?>
                            <?php if($this->countModules('user1 or user2')) : ?>
                                <table class="nopad user1user2">
                                    <tr valign="top">
                                        <?php if($this->countModules('user1')) : ?>
                                            <td>
                                                <jdoc:include type="modules" name="user1" style="xhtml" />
                                            </td>
                                        <?php endif; ?>
                                        <?php if($this->countModules('user1 and user2')) : ?>
                                            <td class="greyline">&nbsp;</td>
                                        <?php endif; ?>
                                        <?php if($this->countModules('user2')) : ?>
                                            <td>
                                                <jdoc:include type="modules" name="user2" style="xhtml" />
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                </table>

                                <div id="maindivider"></div>
                            <?php endif; ?>

                            <table class="nopad">
                                <tr valign="top">
                                    <td>
                                        <jdoc:include type="component" />
                                        <jdoc:include type="modules" name="footer" style="xhtml"/>
                                    </td>
                                    <?php if($this->countModules('right') and JRequest::getCmd('layout') != 'form') : ?>
                                        <td class="greyline">&nbsp;</td>
                                        <td width="170">
                                            <jdoc:include type="modules" name="right" style="xhtml"/>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            </table>

                        </div>
                        <div class="clr"></div>
                    </div>
                    <div class="clr"></div>
                </div>

                <div id="whitebox_b">
                    <div id="whitebox_bl">
                        <div id="whitebox_br"></div>
                    </div>
                </div>
            </div>

            <div id="footerspacer"></div>
        </div>

        <div id="footer">
            <div id="footer_l">
                <div id="footer_r">
                    <p id="syndicate">
                        <jdoc:include type="modules" name="syndicate" />
                    </p>
                    <p id="power_by">
                        <?php echo JText::_('Powered by') ?> <a href="http://www.joomla.org">Joomla!</a>.
                        <?php echo JText::_('Valid') ?> <a href="http://validator.w3.org/check/referer">XHTML</a> <?php echo JText::_('and') ?> <a href="http://jigsaw.w3.org/css-validator/check/referer">CSS</a>.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
<jdoc:include type="modules" name="debug" />
*/
?>
</body>
</html>