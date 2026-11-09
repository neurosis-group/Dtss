<!-- ================ End footer Area ================= -->
  <script src="js/vendor/jquery-2.2.4.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js" integrity="sha384-b/U6ypiBEHpOf/4+1nzFpr53nxSS+GLCkfwBdFNTxtclqqenISfwAzpKaMNFNmj4"
    crossorigin="anonymous"></script>
  <script src="js/vendor/bootstrap.min.js"></script>
  <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBhOdIF3Y9382fqJYt5I_sswSrEw5eihAA"></script>
  <script src="js/jquery.ajaxchimp.min.js"></script>
  <script src="js/jquery.magnific-popup.min.js"></script>
  <script src="js/parallax.min.js"></script>
  <script src="js/owl.carousel.min.js"></script>
  <script src="js/jquery.sticky.js"></script>
  <script src="js/hexagons.min.js"></script>
  <script src="js/jquery.counterup.min.js"></script>
  <script src="js/waypoints.min.js"></script>
  <script src="js/jquery.nice-select.min.js"></script>
  <script src="js/parsley.min.js"></script>
  <script src="js/main.js"></script>
 <script src="js/parsley.js"></script>
 <script>
var acc = document.getElementsByClassName("current_latest_boxs");
var i;
for (i = 0; i < acc.length; i++) {
  acc[i].addEventListener("click", function() {
    this.classList.toggle("active");
    var panel = this.nextElementSibling;
    if (panel.style.display === "block") {
      panel.style.display = "none";
    } else {
      panel.style.display = "block";
    }
  });
}
</script>


<script>
function openCity(evt, cityName) {
  var i, tabcontent, tablinks;
  tabcontent = document.getElementsByClassName("com-tabcontent");
  for (i = 0; i < tabcontent.length; i++) {
    tabcontent[i].style.display = "none";
  }
  tablinks = document.getElementsByClassName("tablinks");
  for (i = 0; i < tablinks.length; i++) {
    tablinks[i].className = tablinks[i].className.replace(" active", "");
  }
  document.getElementById(cityName).style.display = "block";
  evt.currentTarget.className += " active";
}

// Get the element with id="defaultOpen" and click on it
var _dtssDefaultTab = document.getElementById("defaultOpen");
if (_dtssDefaultTab) { _dtssDefaultTab.click(); }
</script>



<script language='JavaScript' type='text/javascript'>
  /* ---------------------------------------------------------------------
   * DTSS enquiry forms - CAPTCHA removed.
   * Validation is handled by Parsley on the client and re-validated
   * server side in store_facility_audit.php / mail.php.
   * ------------------------------------------------------------------ */

  function dtssSubmitForm(selector) {
    var $form = jQuery(selector);
    if (!$form.length) { return false; }

    if ($form.parsley && !$form.parsley().validate()) { return false; }

    var $btn = $form.find('button[type="button"], button[type="submit"]');
    $btn.prop('disabled', true).addClass('is-submitting');

    $form.get(0).submit();
    return true;
  }

  /* Book a Free Facility Audit - popup form in header.php */
  function function_form_submit() {
    return dtssSubmitForm('#form');
  }

  /* Book a Free Facility Audit - home banner form in index.php
     (also used by the careers resume form, which keeps its own action) */
  function function_form_submit1() {
    return dtssSubmitForm('#form1');
  }

  /* Kept as no-ops so any leftover onclick="refreshCaptcha()" never throws. */
  function refreshCaptcha() { return false; }
  function refreshCaptcha1() { return false; }
</script>
        <script type="text/javascript">
          setTimeout(function() {
            $('.alert-success').fadeOut('fast');
        }, 3000);
        </script>

        <!-- Start of dtsschatbothelp Zendesk Widget script --><script id="ze-snippet" src="https://static.zdassets.com/ekr/snippet.js?key=0a68b0af-07c1-46fd-bad0-3080b1e5cb4e"> </script><!-- End of dtsschatbothelp Zendesk Widget script -->

<!-- Linkedin Campaign Manager Tag -->        
        <script type="text/javascript">
_linkedin_partner_id = "9264706";
window._linkedin_data_partner_ids = window._linkedin_data_partner_ids || [];
window._linkedin_data_partner_ids.push(_linkedin_partner_id);
</script><script type="text/javascript">
(function(l) {
if (!l){window.lintrk = function(a,b){window.lintrk.q.push([a,b])};
window.lintrk.q=[]}
var s = document.getElementsByTagName("script")[0];
var b = document.createElement("script");
b.type = "text/javascript";b.async = true;
b.src = "https://snap.licdn.com/li.lms-analytics/insight.min.js";
s.parentNode.insertBefore(b, s);})(window.lintrk);
</script>
<noscript>
<img height="1" width="1" style="display:none;" alt="" src="https://px.ads.linkedin.com/collect/?pid=9264706&fmt=gif" />
</noscript>
        