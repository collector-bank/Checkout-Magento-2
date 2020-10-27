define([
    'uiComponent'
], function (Element) {
    'use strict';
    return Element.extend({
        defaults: {
            isLoaded: false,
            src: "",
            dataToken: ""
        },
        initialize: function (config) {
            this._super();
            this.src = config.src;
            this.dataToken = config.dataToken;

            if (this.src && this.dataToken) {
                this.isLoaded = true;
            }
            this.loadCollectorIframe();
        },
        loadCollectorIframe: function() {
            var collectorIframe = document.createElement("script");
            collectorIframe.src = this.src;
            collectorIframe.setAttribute('data-token',this.dataToken);
            document.getElementById("collectorIframe").appendChild(collectorIframe);
        }
    });
});
