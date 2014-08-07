<meta charset="utf-8">
<meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="author" content="PubCie C.S.R. Delft" />
<meta name="description" content="{Instellingen::get('thuispagina', 'beschrijving')}">
<meta name="robots" content="index, follow" />
<title>C.S.R. Delft - {$body->getTitel()}</title>
<link rel="shortcut icon" href="{$CSR_PICS}/layout/favicon.ico" />
<link rel="alternate" title="C.S.R. Delft RSS" type="application/rss+xml" href="http://csrdelft.nl/forum/rss.xml" />
{foreach from=$view->getStylesheets() item=sheet}
<link rel="stylesheet" href="{$sheet}" type="text/css" />
{/foreach}
{foreach from=$view->getScripts() item=script}
<script type="text/javascript" src="{$script}"></script>
{/foreach}
<script type="text/javascript">
{literal}
var _gaq = _gaq || [];
_gaq.push(['_setAccount', 'UA-19828019-4']);
_gaq.push(['_trackPageview']);
(function() {
var ga = document.createElement('script');
ga.type = 'text/javascript';
ga.async = true;
ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
var s = document.getElementsByTagName('script')[0];
s.parentNode.insertBefore(ga, s);
})();
{/literal}
</script>
<!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
<!--[if lt IE 9]>
<script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
<![endif]-->
<meta name="google-site-verification" content="zLTm1NVzZPHx7jiGHBpe4HeH1goQAlJej2Rdc0_qKzE" />
<meta property="og:url" content="http://csrdelft.nl{Instellingen::get('stek', 'request')}" />
<meta property="og:title" content="C.S.R. Delft | {$view->getTitel()}" />
<meta property="og:locale" content="nl_nl" />
<meta property="og:image" content="http://plaetjes.csrdelft.nl/layout/beeldmerk.png" />
<meta property="og:description" content="{Instellingen::get('thuispagina', 'beschrijving')}" />