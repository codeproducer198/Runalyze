Runalyze.Sport = (function ($) {

    // Public

    var self = {};

    var zonebounds = null;
    var templateZone = null;
    var templateZoneId = null;
    var templateZoneName = null;

    function initHandlers() {
        zonebounds.find('.zonebound-add').click(function () {
            zonebounds.find('.bounds').append(templateZone);
            reBuildBounds();
        });

        copyBound = function () {
            var $p = $(this).parent();
            $p.clone().insertAfter($p);

            reBuildBounds();
        };
        removeBound = function () {
            $(this).parent().remove();
            reBuildBounds();
        };

        function reBuildBounds() {
            // reindex id and name for the symphony mapping of view/model
            var i = 0;
            zonebounds.find('li').each(function () {
                $(this).find('.zonebound-bpm input').attr('id', templateZoneId.replace(/__name__/g, i));
                $(this).find('.zonebound-bpm input').attr('name', templateZoneName.replace(/__name__/g, i++));
            })
            // re-register actions (so the new zones will be also triggered)
            zonebounds.find(".zonebound-copy").unbind('click').click(copyBound);
            zonebounds.find(".zonebound-remove").unbind('click').click(removeBound);
        }

        reBuildBounds();
    }

    self.init = function (elem) {
        zonebounds = $(elem.find('#hrZoneBounds'));

        // extract data from pototyp, backup id&name and then remove it
        var templateZoneProto = zonebounds.find('.bound-prototype');
        templateZone = '<li>' + templateZoneProto.html() + '</li>';
        templateZoneId = zonebounds.find('.bound-prototype input').attr('id');
        templateZoneName = zonebounds.find('.bound-prototype input').attr('name');
        templateZoneProto.remove();

        initHandlers();
    };

    return self;
})(jQuery);
