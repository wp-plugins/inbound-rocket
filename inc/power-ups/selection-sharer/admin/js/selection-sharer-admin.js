jQuery(document).ready( function ( $ ) {
	$('#ir_ss_service_bitly').on('click',function(){
		$('.awesm').hide();
		$('.bitly').show();
	});
	$('#ir_ss_api_service_awesm').on('click',function(){
		$('.bitly').hide();
		$('.awesm').show();
	});
	$('#ir_ss_service_googl').on('click',function(){
		$('.bitly,.awesm').hide();
	});
});