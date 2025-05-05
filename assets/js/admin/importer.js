window.wp = window.wp || {};

wp.Bandit = (function (Bandit, $) {
    Bandit.Importer = {
        initialized: false,

        init: function () {
            if (!this.initialized) this.bind();

            this.initialized = true;
            return this;
        },

        bind: function () {
            $('.js-importer-start').on('click', this.start);
        },

        start: function (event) {
            event.preventDefault();

            var progress = new Bandit.Progress();
            progress.start();

            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: {
                    action: 'wp_bandit_import',
                },
                success: function (response) {
                    console.log('Import started successfully');
                },
            }).done(function () {
                progress.stop();
            });
        },
    };

    /**
     * Class for controlling progress
     */

    Bandit.Progress = function () {
        this.el = null;
        this.timer = null;
        this.delay = 1000;
        this.percent = 0;
        this.text = '';
        this.template =
            '<progress class="progress__bar js-progress-bar" value="0" max="100"></progress>' +
            '<span class="progress__text js-progress-text"></span>';
    };

    Bandit.Progress.prototype.start = function () {
        var that = this;
        console.log('Starting progress bar');
        that.el = $('.progress').append(that.template);
        that.timer = setInterval(function () {
            that.send();
        }, that.delay);
    };

    Bandit.Progress.prototype.stop = function () {
        clearInterval(this.timer);
        this.el.empty();
    };

    Bandit.Progress.prototype.send = function () {
        $.ajax({
            url: ajaxurl,
            type: 'post',
            data: { action: 'wp_bandit_progress_poll' },
            dataType: 'json',
        }).done($.proxy(this.update, this));
    };

    Bandit.Progress.prototype.update = function (data) {
        if (typeof data !== 'undefined') {
            this.percent = Math.floor((data.indexed / data.total) * 100);
            this.text = data.text;

            this.el.children('.js-progress-bar').val(this.percent);
            this.el
                .children('.js-progress-text')
                .html(data.text + ' - ' + this.percent + '%');
        }
    };

    $(function () {
        wp.Bandit.Importer.init();
    });

    return Bandit;
})(wp.Bandit || {}, jQuery);