/**
 * jQuery Batch Plugin v1.0.0
 * This class provide javascript batch/checkboxes features
 * 
 * Copyright 2013, 2014 Maxime CORSON <maxime.corson@spyrit.net>
 * 
 * Released under the MIT license
 */
(function ($) {
    $.fn.batch = function(options) {
        
        var defaults = {
            batchOneSelector: '.batch-one',
            batchAllSelector: '.batch-all',
            batchAllGloballySelector: false, //'.popover .batch-all-globally',
            displayActions: function(){},
            hideActions: function(){},
            
        };
        
        var defaultData = {
            type: 'include',
            checked: []
        };
        
        return this.each(function() {
            var $this = $(this);
            var settings = $.extend(true, {}, defaults, options, $this.data());
            
            var datagrid = {
                name: null,
                previousBatchedElement: null,
                init: function(params){
                    this.name = $this.attr('id');
                    this.params = params;
                    this.listenBatchOne();
                    this.listenBatchAll();
                    this.listenBatchAllGlobally();
                    this.listenBatchShifted();
                    this.updateBatchAllCheckbox();
                    this.toggleActionsArea();
                },
                listenBatchOne: function(){
                    var self = this;
                    $this.on('change', this.params.batchOneSelector, function(){
                        if($(this).prop('checked')){
                            self.batchOne($(this));
                        }else{
                            self.unbatchOne($(this));
                        }
                        self.toggleActionsArea();
                    });
                },
                listenBatchAll: function(){
                    var self = this;
                    $this.on('change', this.params.batchAllSelector, function(){
                        if($(this).prop('checked')){
                            self.batchAll($(this));
                        }else{
                            self.unbatchAll($(this));
                        }
                        self.toggleActionsArea();
                    });
                },
                listenBatchAllGlobally: function(){
                    if(this.params.batchAllGloballySelector !== false)
                    {
                        var self = this;
                        $this.on('click', this.params.batchAllGloballySelector, function(){
                            self.batchAllGlobally($(this));
                            self.toggleActionsArea();
                        });
                    }
                },
                listenBatchShifted: function(){
                    var self = this;
                    $this.on('click', this.params.batchOneSelector, function(evt) {
                        if(evt.shiftKey){
                            var $refBatch = self.previousBatchedElement;
                            if($refBatch){
                                var refBatchIndex = $refBatch.parents('tr').index();
                                var checkedBatchIndex = $(this).parents('tr').index();

                                var minPosition = (refBatchIndex < checkedBatchIndex)? refBatchIndex : checkedBatchIndex;
                                var maxPosition = (refBatchIndex < checkedBatchIndex)? checkedBatchIndex : refBatchIndex;

                                var $checks = $(this).parents('table')
                                    .find(self.params.batchOneSelector);

                                $checks.each(function(index){
                                    if(index >= minPosition && index <= maxPosition ){
                                        if($refBatch.prop('checked')){
                                            self.batchOne($(this));
                                            $(this).prop('checked', true);
                                        }else{
                                            self.unbatchOne($(this));
                                            $(this).prop('checked', false);
                                        }
                                    }
                                });
                            }
                            self.toggleActionsArea();
                        }
                    });
                },
                getData: function(){
                    var data = $.cookie(this.name+'_batch');
                    if(!data){
                        return defaultData;
                    }
                    return JSON.parse(data);
                },
                setData: function(data){
                    $.cookie(
                        this.name+'_batch', 
                        JSON.stringify(data), 
                        { expires: 7, path: '/' }
                    );
                },  
                addChecked: function(value){
                    var data = this.getData();
                    data.checked.push(value);
                    this.setData(data);
                },
                removeChecked: function(value){
                    var data = this.getData();
                    var index = data.checked.indexOf(value);
                    if(index > -1) {
                        data.checked.splice(index, 1);
                        this.setData(data);
                    }
                },
                batchAll: function($elt){
                    var self = this;

                    $elt.popover('show');
                    setTimeout(function(){$elt.popover('hide')}, 10000);

                    var $checkboxes = $this.find(this.params.batchOneSelector);

                    $checkboxes.each(function(index){
                        $(this).prop('checked', 'checked');
                        self.addChecked($(this).val())
                    });
                },
                unbatchAll: function($elt){
                    this.setData(defaultData);
                    $elt.popover('hide');
                    $this.find(this.params.batchOneSelector).prop('checked', false);
                    this.updateBatchAllCheckbox();
                },
                batchAllGlobally: function($elt){
                    this.setData({
                        type: 'exclude',
                        checked: []
                    });
                    $(this.params.batchAllSelector).popover('hide');
                    var $checkboxes = $this.find(this.params.batchOneSelector);

                    $checkboxes.each(function(index){
                        $(this).prop('checked', 'checked');
                    });
                },
                batchOne: function($elt){
                    if(this.getData().type === 'include'){
                        this.addChecked($elt.val());
                    }
                    else{
                        this.removeChecked($elt.val());
                    }
                    this.updateBatchAllCheckbox();
                    this.previousBatchedElement = $elt;
                    $('#batch-actions').show();
                },
                unbatchOne: function($elt){
                    if(this.getData().type === 'include'){
                        this.removeChecked($elt.val());
                    }else{
                        this.addChecked($elt.val());
                    }
                    this.updateBatchAllCheckbox();
                    this.previousBatchedElement = $elt;
                },
                updateBatchAllCheckbox: function(){
                    var data = this.getData();
                    var $checkbox = $this.find(this.batchAllSelector);
                    
                    if(data.type === 'include'){
                        if(data.checked.length > 0)
                            $checkbox.prop('indeterminate', true);
                        else
                            $checkbox.prop('checked', false);
                    }else{
                        if(data.checked.length === 0)
                            $checkbox.prop('checked', true);
                        else
                            $checkbox.prop('indeterminate', true);
                    }
                },
                hasBatchedData: function(){
                    var data = this.getData();
                    if(data){
                        if(data.type === 'include' && data.checked.length > 0){
                            return true;
                        }else if(data.checked.type === 'exclude'){
                            return true;
                        }
                    }
                    return false;
                },
                toggleActionsArea: function(){
                    if(this.hasBatchedData()){
                        settings.showAction();
                    }else{
                        settings.hideAction();
                    }
                }
            };
            
            datagrid.init(settings);
        });
    };
}(jQuery)); 