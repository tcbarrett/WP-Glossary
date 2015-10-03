(function($){
    var baseTouch = ( navigator.userAgent.match(/Android/i)
                     || navigator.userAgent.match(/webOS/i)
                     || navigator.userAgent.match(/iPhone/i)
                     || navigator.userAgent.match(/iPad/i)
                     || navigator.userAgent.match(/iPod/i)
                     || navigator.userAgent.match(/BlackBerry/i) ) ? 1 : 0;
    $(document).ready(function(){
        $('span[class*=wpg-tooltip]').each(function(){
            var ajaxPostData = $.extend( {action: 'wpg_get_term_details'}, $(this).data() );
            var qtipstyle    = $(this).data('qtipstyle');

            // If set to click, disable glossary link
            if( WPG.qtiptrigger == 'click' ){
                $(this).children('a').click(function(e){
                    e.preventDefault();
                });
            } else if( WPG.qtiptrigger == 'responsive' ){
                var self = $(this);
                self.touch = baseTouch;

                //Detect touch/click out
                $(document).click(function(event) { 
                    if(!$(event.target).closest(self).length) {
                        self.data("expanded", false);
                        self.triggerHandler("responsiveout");

                    }
                });

                self.children('a').click(function(e){
                    if(!self.data("expanded") && self.touch != 0){
                        self.data("expanded", true);
                        self.triggerHandler("responsive");
                        e.preventDefault();
                    }
                }).bind("touchstart", function(e){
                    self.touch = 1
                }).bind("touchend", function(e){
                    self.touch = 2;
                }).mouseover(function(e){
                    self.triggerHandler("responsive");
                }).mouseleave(function(e){
                    self.triggerHandler("responsiveout");
                });
            }

            $(this).qtip({
                content: {
                    text: 'Loading glossary term',
                    ajax: {
                        url     : WPG.admin_ajax,
                        type    : 'POST',
                        data    : ajaxPostData,
                        dataType: 'json',
                        loading : false,
                        success : function(resp, status){
                            if( resp.success ) {
                                this.set( 'content.title', resp.data.title );
                                this.set( 'content.text',  resp.data.content );
                            } else {
                                this.set( 'content.text', 'Error' );
                            }
                        }
                    },
                    title: { text: 'Glossary Title' }
                },
                prerender: true,
                position: {
                    at      : 'bottom center', // Position the tooltip above the link
                    my      : 'top center',
                    viewport: $("body"),       // Keep the tooltip on-screen at all times
                    effect  : false            // Disable positioning animation
                },
                show: {
                    event: WPG.qtiptrigger,
                    solo:  true // Only show one tooltip at a time
                },
                //hide: 'unfocus',
                hide: (WPG.qtiptrigger == 'responsive') ? "responsiveout" : 'mouseleave',
                style: { 
                    classes: 'qtip-'+qtipstyle+' qtip-shadow qtip-rounded'
                }
            })
        });
    });
})(jQuery);
