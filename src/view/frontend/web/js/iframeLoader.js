define([
    'uiComponent'
], function (Element) {
    'use strict';
    return Element.extend({
        defaults: {
            isLoaded: false,
            src: "",
            dataToken: "",
            dataVersion: "",
            dataActionColor: "",
            dataActionTextColor: "",
            dataLang: ""
        },
        initialize: function (config) {
            this._super();
            this.src = config.src;
            this.dataToken = config.dataToken;
            this.dataVersion = config.dataVersion;
            this.dataActionColor = config.dataActionColor;
            this.dataActionTextColor = config.dataActionTextColor;
            this.dataLang = config.dataLang;
            if (this.src && this.dataToken) {
                this.isLoaded = true;
            }
            this.loadCollectorIframe();
        },
        loadCollectorIframe: function() {
            var collectorIframe = document.createElement("script");
            collectorIframe.src = this.src;
            collectorIframe.setAttribute('data-token', this.dataToken);
            collectorIframe.setAttribute('data-version', this.dataVersion);
            collectorIframe.setAttribute('data-lang', this.dataLang);
            collectorIframe.setAttribute('data-action-color', this.dataActionColor);
            collectorIframe.setAttribute('data-action-text-color', this.dataActionTextColor);
            document.getElementById("collectorIframe").appendChild(collectorIframe);
        }
    });
});