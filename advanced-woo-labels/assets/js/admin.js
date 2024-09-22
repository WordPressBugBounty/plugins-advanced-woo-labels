jQuery(document).ready(function ($) {
    'use strict';

    var $settingsTable = $('.awl-label-settings-table');


    // Quick edit panel
    $( '#the-list' ).on( 'click', '.editinline', function() {
        var row = $(this).closest( '.type-awl-labels.hentry' );
        if ( row.length ) {
            var priority = row.find('.column-label_priority').text();
            if ( priority !== '' ) {
                var inlineRow = $('.inline-edit-awl-labels');
                if ( inlineRow.length ) {
                    inlineRow.find('input[name="awl_label_priority_val"]').val(priority);
                }
            }
        }
    });


    // remove edit links
    $('#misc-publishing-actions a.edit-timestamp').remove();


    $('.awl-color-picker').wpColorPicker( {
        palettes: false,
        change: function (event, ui) {
            var element = event.target;
            var color = ui.color.toString();
            $(element).val(color).trigger('change');
        }
    } );


    $( '.awl-help-tip' ).tipTip( {
        'attribute': 'data-tip',
        'fadeIn': 50,
        'fadeOut': 50,
        'delay': 200,
        'keepAlive': true
    } );


    $( '.awl-content-var-item' ).tipTip( {
        'attribute': 'data-text-var-tip',
        'fadeIn': 50,
        'fadeOut': 50,
        'delay': 50,
    } );


    $( '.awl-content-var-item' ).on('click', function(e) {
        if (navigator.clipboard) {
            var text = $(this).find('span').text();
            navigator.clipboard.writeText(text).then(function() {
                $( '#tiptip_content' ).text( awl_vars.copy_text );
            }, function(err) {
                console.error('Unable to copy text: ', err);
            });
        }
    });


    // Select2 init
    function awl_init_select2() {

        $('#awl_label_conditions select.awl-select2').select2({
            minimumResultsForSearch: 15
        });

        var awlSelect2Ajax = $('#awl_label_conditions select.awl-select2-ajax');
        if ( awlSelect2Ajax.length > 0 ) {

            awlSelect2Ajax.each(function( index ) {

                var ajaxAction = $(this).data('ajax');
                var placeholder = $(this).data('placeholder');

                $(this).select2({
                    ajax: {
                        type: 'POST',
                        delay: 250,
                        url: awl_vars.ajaxurl,
                        dataType: "json",
                        data: function (params) {
                            return {
                                search: params.term,
                                action: ajaxAction,
                                _ajax_nonce: awl_vars.ajax_nonce
                            };
                        },
                    },
                    placeholder: placeholder,
                    minimumInputLength: 3,
                });
            });
        }

    }

    awl_init_select2();


    // Labels ajax status toggler
    var $toggler = $('.awl-ajax-toggle');

    $toggler.on( 'click', function(e) {
        e.preventDefault();

        var $this = $(this);
        var id = $this.data('label-id');

        if ( $this.hasClass('awl-precessing') ) {
            return false;
        }

        $this.addClass('awl-precessing');

        $.ajax({
            type: 'POST',
            url: awl_vars.ajaxurl,
            dataType: "json",
            data: {
                action: 'awl-changeLabelStatus',
                id: id,
                _ajax_nonce: awl_vars.ajax_nonce
            },
            success: function (response) {
                if ( response ) {
                    $this.removeClass('awl-precessing');
                    $this.toggleClass('active');
                }
            }
        });

    });


    var awlUniqueID = function() {
        return Math.random().toString(36).substr(2, 11);
    };


    var awlGetRuleTemplate = function( groupID, ruleID) {

        var template = $(this).closest('.awl-rules').find('#awlRulesTemplate').html();

        if ( typeof groupID !== 'undefined' ) {
            template = template.replace( /\[group_(.+?)\]/gi, '[group_'+groupID+']' );
        }

        if ( typeof ruleID !== 'undefined' ) {
            template = template.replace( /\[rule_(.+?)\]/gi, '[rule_'+ruleID+']' );
            template = template.replace( /data-awl-rule="(.+?)"/gi, 'data-awl-rule="'+ruleID+'"' );
        }

        return template;

    };


    $(document).on( 'click', '[data-awl-remove-rule]', function(e) {
        e.preventDefault();
        var $table = $(this).closest('.awl-rules-table');
        $(this).closest('[data-awl-rule]').remove();

        if ( $table.find('[data-awl-rule]').length < 1 ) {
            $table.remove();
        }

    });


    $(document).on( 'click', '[data-awl-add-rule]', function(e) {
        e.preventDefault();

        var groupID = $(this).closest('.awl-rules-table').data('awl-group');
        var ruleID = awlUniqueID();
        var rulesTemplate = awlGetRuleTemplate.call(this, groupID, ruleID);

        $(this).closest('.awl-rules-table').find( '.awl-rule' ).last().after( rulesTemplate );

    });


    $(document).on( 'click', '[data-awl-add-group]', function(e) {
        e.preventDefault();

        var groupID = awlUniqueID();
        var rulesTemplate = awlGetRuleTemplate.call(this, groupID);

        rulesTemplate = '<table class="awl-rules-table" data-awl-group="' + groupID + '"><tbody>' + rulesTemplate + '</tbody></table>';
        $(this).closest('.awl-rules').find('.awl-rules-table').last().after( rulesTemplate );

    });


    $(document).on('change', '[data-awl-param]', function(evt, params) {

        var newParam = this.value;
        var ruleGroup = $(this).closest('[data-awl-rule]');

        var ruleOperator = ruleGroup.find('[data-awl-operator]');
        var ruleValues = ruleGroup.find('[data-awl-value]');
        var ruleParams = ruleGroup.find('[data-awl-param]');
        var ruleSuboptions = ruleGroup.find('[data-awl-suboption]');

        var ruleID = ruleGroup.data('awl-rule');
        var groupID = $(this).closest('[data-awl-group]').data('awl-group');

        ruleGroup.addClass('awl-pending');

        if ( ruleSuboptions.length ) {
            ruleSuboptions.remove();
            ruleGroup.find('.select2-container').remove();
        }

        $.ajax({
            type: 'POST',
            url: awl_vars.ajaxurl,
            dataType: "json",
            data: {
                action: 'awl-getRuleGroup',
                name: newParam,
                ruleID: ruleID,
                groupID: groupID,
                _ajax_nonce: awl_vars.ajax_nonce
            },
            success: function (response) {
                if ( response ) {

                    ruleGroup.removeClass('adv');

                    if ( typeof response.data.aoperators !== 'undefined' ) {
                        ruleOperator.html( response.data.aoperators );
                    }

                    if ( typeof response.data.avalues !== 'undefined' ) {
                        ruleValues.html( response.data.avalues );
                    }

                    if ( typeof response.data.asuboptions !== 'undefined' ) {
                        ruleParams.after( response.data.asuboptions );
                        ruleGroup.addClass('adv');
                    }

                    ruleGroup.removeClass('awl-pending');

                    awl_init_select2();

                }
            }
        });

    });

    $(document).on('change', '[data-awl-suboption]', function(evt, params) {

        var suboptionParam = this.value;
        var ruleGroup = $(this).closest('[data-awl-rule]');
        var ruleParam = ruleGroup.find('[data-awl-param] option:selected').val();
        var ruleValues = ruleGroup.find('[data-awl-value]');

        var ruleID = ruleGroup.data('awl-rule');
        var groupID = $(this).closest('[data-awl-group]').data('awl-group');

        ruleGroup.addClass('awl-pending');

        $.ajax({
            type: 'POST',
            url: awl_vars.ajaxurl,
            dataType: "json",
            data: {
                action: 'awl-getSuboptionValues',
                param: ruleParam,
                suboption: suboptionParam,
                ruleID: ruleID,
                groupID: groupID,
                _ajax_nonce: awl_vars.ajax_nonce
            },
            success: function (response) {
                if ( response ) {
                    ruleValues.html( response.data );
                    ruleGroup.removeClass('awl-pending');
                    awl_init_select2();
                }
            }
        });

    });

    $(document).on('change', '#awl-label-params-settings-custom-styles', function(evt, params) {
        $settingsTable.toggleClass('awl-show-styles');
    });

    $(document).on('change', '#awl-label-params-settings-position-type', function(evt, params) {

        var positionType = this.value;
        var positionClass = 'awl-position-on-line';
        var currentPositionClass = '';

        $settingsTable.attr('class', function(i, c){
            var classRegex = /awl-position-\S+/g;
            currentPositionClass = c.match(classRegex)[0];
            return c.replace(classRegex, '');
        });

        if ( positionType === 'on_image' ) {
            positionClass = 'awl-position-on-image';
        }

        // Change position value to best matched
        var positionFieldOld = $('#awl-label-params-settings-position');
        var positionFieldNew = $('#awl-label-params-settings-position-x');

        if ( currentPositionClass && currentPositionClass !== positionClass ) {

            if ( positionType === 'on_image' ) {

                positionFieldOld = $('#awl-label-params-settings-position-x');
                positionFieldNew = $('#awl-label-params-settings-position');

                if ( positionFieldNew.val().indexOf( positionFieldOld.val() ) !== 0 ) {
                    positionFieldNew.val( positionFieldOld.val() + '_top' );
                }

            } else {

                var regex = new RegExp('([\\w]+)_([\\w]+)', 'g');
                var positionsArr = regex.exec( positionFieldOld.val() );

                if ( positionsArr && positionsArr[1] !== positionFieldNew.val() ) {
                    positionFieldNew.val( positionsArr[1] );
                }

            }

        }

        $settingsTable.addClass( positionClass );
        positionFieldNew.trigger('change');

    });

    $(document).on('change', '#awl-label-params-settings-type', function(evt, params) {

        var labelType = this.value;

        var typeOption = $('[data-option-id="type"]');
        var templatesSelector = $('[data-template-select]');

        typeOption.addClass('awl-pending');

        window.setTimeout(function(){

            $settingsTable.attr('class', function(i, c){
                return c.replace(/awl-type-\S+/g, '');
            });

            $settingsTable.addClass( 'awl-type-' + labelType );

            var firstOption = templatesSelector.find('[data-templates="' + labelType + '"]').find('.option:first-of-type');
            awl_set_template( firstOption );
            typeOption.removeClass('awl-pending');

        }, 500);

    });

    $(document).on('click', '[data-awl-template]', function(e, params) {

        if (  ! $(e.target).closest('[data-template-select]').length ) {
            $(this).toggleClass('awl-active');
        }


    });

    $(document).on( 'click', function (e) {

        if (  ! $(e.target).closest('[data-awl-template]').length ) {
            $('[data-awl-template]').removeClass('awl-active');
        }

    });

    $(document).on('click', '[data-template-select] .option', function(evt, params) {
        awl_set_template( $(this) );
    });

    // Image upload
    $('[data-awl-upload]').on('click', function(e) {

        e.preventDefault();

        var option = $('[data-templates="image"]').find('.option:first-of-type');
        var size = 'thumbnail';
        var custom_uploader;

        //If the uploader object has already been created, reopen the dialog
        if (custom_uploader) {
            custom_uploader.open();
            return;
        }

        //Extend the wp.media object
        custom_uploader = wp.media.frames.file_frame = wp.media({
            title: 'Choose Image',
            button: {
                text: 'Choose Image'
            },
            multiple: false,
            type : 'image'
        });

        custom_uploader.on('select', function() {
            var attachment = custom_uploader.state().get('selection').first().toJSON();

            var image_size = attachment.sizes['full'];
            var image_src = image_size.url;

            var newOption = option.clone( true );
            newOption.data('val', image_src);
            newOption.find('.ico').css('background-image','url('+image_src+')');

            newOption.insertBefore( option );

            var templatesBox = $('[data-awl-template]');
            templatesBox.addClass('awl-pending');

            window.setTimeout(function(){
                awl_set_template(newOption);
                templatesBox.removeClass('awl-pending');
            }, 1000);

        });

        //Open the uploader dialog
        custom_uploader.open();

    });


    $('.additional-info').on('click', function(e) {
        e.preventDefault();
        if ( ! $(e.target).closest( '.info-spoiler' ).length ) {
            $(this).find('.info-spoiler').toggleClass('show');
        }
    });


    $('.additional-info .info-spoiler a').on('click', function(e) {
        e.stopPropagation();
    });


    function awl_set_template( $el ) {
        $('.awl-template [data-template-select] .option').removeClass('awl-active');
        $('.awl-template [data-current-template]').attr('style', $el.find('.ico').attr('style') );
        $('.awl-template [data-template-val]').val( $el.data('val') ).trigger('change');
        $el.addClass('awl-active');
    }


    // Hooks table

    var awlGetHookTableTemplate = function( hookID) {

        var template = $('#awlHooksTableTemplate').html();

        if ( typeof hookID !== 'undefined' ) {
            template = template.replace( /\[hookid_(.+?)\]/gi, '[hookid_'+hookID+']' );
        }

        return template;

    };

    $(document).on( 'change', '.awl-hooks-table [data-awl-hook]', function(e) {
        e.preventDefault();

        var hook = this.value;
        var $container = $(this).closest('td');

        if ( hook === 'custom action' || hook === 'custom filter' || hook === 'custom' ) {
            $container.addClass('awl-custom');
            $container.removeClass('awl-advanced');
        } else if ( hook === 'advanced' ) {
            $container.addClass('awl-custom');
            $container.addClass('awl-advanced');
        } else {
            $container.removeClass('awl-custom');
            $container.removeClass('awl-advanced');
        }

    });

    $(document).on( 'click', '.awl-hooks-table [data-awl-remove-hook]', function(e) {
        e.preventDefault();
        var table = $(this).closest('.awl-hooks-table');
        $(this).closest('[data-awl-hook-container]').remove();
        if ( ! table.find('[data-awl-hook-container]').length > 0 ) {
            table.removeClass('awl-has-hooks');
        }
    });

    $(document).on( 'click', '.awl-hooks-table [data-awl-add-hook]', function(e) {
        e.preventDefault();

        var hookID = awlUniqueID();
        var rulesTemplate = awlGetHookTableTemplate.call(this, hookID);

        $(this).closest('.awl-hooks-table').addClass('awl-has-hooks').find( 'tbody' ).append( rulesTemplate );

    });

    $(document).on( 'change', 'select[name="display_hooks"]', function(e) {
        e.preventDefault();

        var val = this.value;
        var $hooksTable = $('.awl-hooks-table');
        var $hooksRelations = $('select[name="hooks_relation"]');

        if ( val === 'true' ) {
            $hooksTable.removeClass('awl-disabled');
            $hooksRelations.removeClass('awl-disabled');
        } else {
            $hooksTable.addClass('awl-disabled');
            $hooksRelations.addClass('awl-disabled');
        }

    });

    $(document).on( 'change', '.awl-hooks-table .advanced-val-js-pos', function(e) {
        e.preventDefault();
        $(this).removeClass('advanced-val-empty');
    });

    $(document).on( 'click', '.awl-hooks-table [data-awl-generate-hook]', function(e) {
        e.preventDefault();

        var button = $(this);

        if ( window.confirm( awl_vars.confirm_hooks ) ) {

            button.addClass('awl-pending');

            $.ajax({
                type: 'POST',
                url: awl_vars.ajaxurl,
                dataType: "json",
                data: {
                    action: 'awl-showCurrentHooks',
                    _ajax_nonce: awl_vars.ajax_nonce
                },
                success: function (response) {
                    if ( response && response.data ) {
                        button.closest('.awl-hooks-table').addClass('awl-has-hooks').find( 'tbody' ).append( response.data );
                    }
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    alert(xhr.responseText);
                },
                complete: function (xhr, textStatus) {
                    button.removeClass('awl-pending');
                }
            });

        }

    });

});