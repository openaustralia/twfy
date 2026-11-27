{{-- The box people paste into their own site, shown on the Link to us page and offered as code. $url is this site's address. --}}
<!-- OpenAustralia box, begin -->
<div style='position: relative; width: 17em; color: #000000; background-color: #EBECCF; font-family: Arial, Geneva, Sans-serif; margin-bottom: 1em; border: 1px solid #AE967F; padding: 0 10px 2em 10px;'>
  <h5 style='font-family: Arial, Geneva, Sans-serif; font-size: 1.4em; position: absolute; margin: 0; bottom: 2px; right: 10px;' title='OpenAustralia.org'><a href='{{ $url }}' style='color: #957676; text-decoration: none; font-weight: normal;'><em style='font-weight: bold; font-style: normal;'><span style='color: #7CA3B0'>Open</span>Australia</em></a></h5>
  <form action='{{ $url }}mp/' method='get' style='margin: 0; padding: 5px 0 0 0;' title='Find out about your Representative'>
    <label for='pc' style='display: block; font-size: small; font-weight: bold; margin: 0 0 9px 0;'>Find out more about your Representative</label>
    <input id='pc' maxlength='20' name='pc' size='8' style='width: 12em; border: solid 1px #AE967F;' title='Enter your Australian postcode here' type='text' value='Your Postcode'>
    <input id='Submit1' name='Submit1' style='border: solid 0px #AE967F; background-color: #AE967F; color: #ffffff; font-weight: bold; text-transform: uppercase;' title='Submit search' type='submit' value='Go'>
  </form>
  <form action='{{ $url }}search/' method='get' style='margin: 0; padding: 5px 0 0 0;' title='Search Parliament'>
    <label for='s' style='display: block; font-size: small; font-weight: bold; margin: 0 0 2px 0;'>Search Parliament</label>
    <input id='s' maxlength='100' name='s' size='15' style='width: 12em; border: solid 1px #AE967F;' title="Type what you're looking for" type='text' value='Your Search'>
    <input id='Submit2' name='Submit2' style='border: solid 0px #AE967F; background-color: #AE967F; color: #ffffff; font-weight: bold; text-transform: uppercase;' title='Submit search' type='submit' value='Go'>
    <br>
  </form>
</div>
<!-- OpenAustralia box, end -->
