<p><strong>Link Policy:</strong> No person or organisation needs permission to link to us. Be our guest. You can deep link to any page you like, from any page, at any time, without asking.</p>

<p>Alternatively, if you would like to place an OpenAustralia search box on your site, like this one&hellip;</p>

<?
$url = "http://" . DOMAIN . WEBPATH;
$link_to_us_form = <<<END
<!-- OpenAustralia box, begin -->
<div style="position: relative; width: 17em; color: #000000; background-color: #EBECCF; font-family: Arial, Geneva, Sans-serif; margin-bottom: 1em; border: 1px solid #AE967F; padding: 0 10px 2em 10px">
	<h5 style="font-family: Arial, Geneva, Sans-serif; font-size: 1.4em; position: absolute; margin: 0; bottom: 2px; right: 10px" title="OpenAustralia.org"><a style="color: #957676; text-decoration: none; font-weight: normal" href="
END;
$link_to_us_form .= $url;
$link_to_us_form .= <<<END
"><em style="font-weight: bold; font-style: normal"><span style="color:#7CA3B0">Open</span>Australia</em></a></h5>
	<form style="margin: 0; padding: 5px 0 0 0" title="Find out about your Representative" action="
END;
$link_to_us_form .= $url. "mp/";
$link_to_us_form .= <<<END
" method="get">
		<label style="display: block; font-size: small; font-weight: bold; margin: 0 0 9px 0" for="pc">Find out more about your <abbr title="Representative">Representative</abbr></label>
		<input id="pc" name="pc" style="width: 12em; border: solid 1px #AE967F" title="Enter your Australian postcode here" type="text" size="8" maxlength="20" value="Your Postcode"> 
		<input id="Submit1" name="Submit1" style="border: solid 0px #AE967F; background-color: #AE967F; color: #ffffff; font-weight: bold; text-transform: uppercase" title="Submit search" type="submit" value="Go">
	</form>
	<form style="margin: 0; padding: 5px 0 0 0" title="Search Parliament" action="
END;
$link_to_us_form .= $url . "search/";
$link_to_us_form .= <<<END
" method="get">
		<label style="display: block; font-size: small; font-weight: bold; margin: 0 0 2px 0" for="s">Search Parliament</label>
		<input id="s" name="s" style="width: 12em; border: solid 1px #AE967F" title="Type what you&#39;re looking for" type="text" size="15" maxlength="100" value="Your Search">
		<input id="Submit2" name="Submit2" style="border: solid 0px #AE967F; background-color: #AE967F; color: #ffffff; font-weight: bold; text-transform: uppercase" title="Submit search" type="submit" value="Go">
		<br>
	</form>
</div> 
<!-- OpenAustralia box, end -->
END;
print $link_to_us_form;
?>		

<p>Cut and paste the code below into your webpage:</p>
<textarea class="sourcecode" style="width: 100%; height: 20em;">
<? print htmlspecialchars($link_to_us_form); ?>
</textarea>

<p/>
<p>Please retain the link to <a href="<?= $url ?>">OpenAustralia.org</a> (Google points mean prizes). Any questions, just drop us a line at: <a href="<?= $url . 'contact/' ?>"><?= $url . 'contact/' ?></a>
</p>
																																																																																																																																																													
