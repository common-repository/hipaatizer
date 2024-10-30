(function( $ ) {
	'use strict';

	var ifrm = document.createElement('iframe'),
		$url = hipaa_params.url,
        $pluginUrl = hipaa_params.pluginUrl,
		$curl = hipaa_params.curl,
        $query_param = window.location.href.slice(window.location.href.indexOf('?')+1),
        searchParams = new URLSearchParams(window.location.search);
       // $irameH = $(window).height() - 32;

        //ifrm.setAttribute('src', $url);
       // ifrm.style.height = $irameH+'px';

        if ( $url ) {
            location.href = $url+'&pluginUrl='+encodeURIComponent($pluginUrl);
        }

		$( document ).ready(function() {
		if( $('.hipaa-iframe-container').length ){
            $('.hipaa-iframe-container').append(ifrm);
        }
        $('#adminmenu .wp-has-current-submenu ul  li  a').each(function(){
            var $this = $(this),
                $href = $this.attr('href');
            if( $href == '?'+$query_param ) {
                $('#adminmenu .wp-has-current-submenu ul  li.current a').removeClass('current');
                $('#adminmenu .wp-has-current-submenu ul  li.current').removeClass('current');
                $this.addClass('current');
                $this.parent().addClass('current');
            }
        });
        if( searchParams.has('auth') ){
            $('.hipaa-loader').show();
            $('.hipaa-fcontent').hide();
        }
			$('.copyShortcode').on('click', function(e){
                e.preventDefault();
                var $this = $(this),
                $temp = $("<input>"),
                element = $this.next('span');
                $("body").append($temp);
                $temp.val($(element).text()).select();
                document.execCommand("copy");
                $temp.remove();
				$this.find('.tooltip_copied').addClass('show');
				$this.find('.tooltip').hide();
				setTimeout(function(){
					$this.find('.tooltip_copied').removeClass('show');
                    $this.find('.tooltip').show();
				}, 2000);
            });

			$('.hipaa-refresh').on('click',  function () {
				var $type_form = $(this).attr('data-type');
                    $('.hipaa-loader').show();
                    $.ajax({
                    type: 'GET',
                    url: hipaa_params.ajax_url,
                    data:  { action: 'refresh_hipaa_forms',  nonce: hipaa_params.nonce, type: $type_form },
                    success: function(response) {
                        $('#hipaa-list').html(response);
                        $('.hipaa-loader').hide();
                    }


                    });
            });

            $('.hipaa-tabs a').on('click',  function (e) {
                e.preventDefault();
                $('.hipaa-loader').show();
                var $this     = $(this),
                    $type = $this.attr('data-type'),
                    $folderId = $this.attr('data-folderId'),
                    $status   = $this.attr('data-status');

                $('.hipaa-tabs li').removeClass('active');
                $this.parent().addClass('active');
                if( !$status ) {
                    $.ajax({
                    type: 'GET',
                    url: hipaa_params.ajax_url,
                    data:  { action: 'tabs_hipaa_forms', type: $type,  folderId: $folderId },
                    success: function(response) {
                        $('#hipaa-list').html(response);
                        $('.hipaa-loader').hide();
                        }
                    });
                } else {
                    $.ajax({
                        type: 'GET',
                        url: hipaa_params.ajax_url,
                        data:  { action: 'tabs_hipaa_forms', type: $type,  status: $status },
                        success: function(response) {
                            $('#hipaa-list').html(response);
                            $('.hipaa-loader').hide();
                        }
                    });
                }
            });

                function checked_all($el){
                    if( $el.is(':checked') ){
                        $('#export-wpcf7').find('input[type="checkbox"]').prop('checked',true);
                    } else {
                        $('#export-wpcf7').find('input[type="checkbox"]').prop('checked',false);
                    }
                }
                checked_all($('#select_all'));

                $('#select_all').on('change', function() {
                    var $this = $(this);
                    checked_all($this);
                });

                $('#toplevel_page_hipaatizer .item_target_blank').each(function(){
                    $(this).parent().attr('target', '_blank');
                });

                $.urlParam = function(name){
                var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
                if (results==null) {
                    return null;
                }
                return decodeURI(results[1]) || 0;
            }
            if( $.urlParam('change_account') == 1 ){
                $('#item_change_account').parent().parent().addClass('current').siblings().removeClass('current');
            }

			$('.hippa_message').on('click', function(){
                    $(this).hide();
				var uri = window.location.toString();
                if (uri.indexOf("&") > 0) {
                    var clean_uri = uri.substring(0, uri.indexOf("&"));
                    window.history.replaceState({}, document.title, clean_uri);
                }

                });

			$('.hipaaIconMenu').on('click', function(){
                $('body').addClass('hipaa-open-menu');
            });

			$('.hipaaIconClose').on('click', function(){
                $('body').removeClass('hipaa-open-menu');
            });
        });
	function geneate_form($form_id){
                var form_id = $form_id;
                var basicUrl = $curl+form_id;
                var iFrameParent = document.getElementById(form_id);
                var iFrame = document.createElement('iframe');

                iFrameParent.appendChild(iFrame).setAttribute('src', basicUrl)

	}

	$(document).on('change', '.hipaa-select select', function(){
		var $this = $(this);
		setTimeout( function(){
			var $hipaa = $this.parent().next('.hipaa-form'),
            $id = $hipaa.attr('id');
			geneate_form($id);
		},1000);


	});

    $( window ).load(function() {
        const searchParams = new URLSearchParams(window.location.search);

        const accountId = searchParams.get('accountId');

        if (accountId) {
            document.cookie = `hipaaID = ${accountId}`;

            window.location.href = hipaa_params.admin_url+"?page=hipaatizer";
        }

		$('.hipaa-form').each(function(){
			var $id = $(this).attr('id')
			geneate_form($id);
		});
            hipaa_mobile();
	});

	$( window ).resize(function() {
        hipaa_mobile();
    });

    function hipaa_mobile() {
        if( $( window ).width() < 1181) {
            $('.hipaa-changeAccount').appendTo( '.nav-menu' );
        } else {
            $('.nav-menu .hipaa-changeAccount').remove();
        }
    }


})( jQuery );

window.addEventListener('message', event => {
    const eventData = event.data;
    const identityHeader = '#accountId';

    if (typeof eventData === 'string' && eventData.includes(identityHeader)){
        const accountId = eventData.substring(identityHeader.length);

        //ACCOUNTID
        console.log('accountId', accountId)

		document.cookie = `hipaaID = ${accountId}`;
		window.location.href = hipaa_params.admin_url+"?page=hipaatizer";
    }
});
var logout = location.search.split('hipaa_account=')[1]
	if ( logout == 'login' ){
        document.cookie = 'hipaaID=; Max-Age=0'
	}

window.fwSettings = {
    widget_id: 72000002319,
    };
    !(function () {
    if ("function" != typeof window.FreshworksWidget) {
        var n = function () {
        n.q.push(arguments);
    };
    (n.q = []), (window.FreshworksWidget = n);
    }
})();