<?php
/**
 * CORE! Google Javascript
 * 
 * Analytics
 * Sign-In
 * Plus
 */
?>
<script src="https://maps.googleapis.com/maps/api/js?v=3.exp"></script>

<?
// Analytics
if($this->analyticsId) {
?>
	<!-- Google Analytics -->
	<script>
		(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

		ga('create', '<?=$this->analyticsId?>', 'auto');
		ga('send', 'pageview');
	</script>
<?
}
?>