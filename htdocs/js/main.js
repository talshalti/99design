/*
    @file main.js

    @ since 13/11/2014
    @ author Alex Ponomarev
 */


var request = null;
var hostUrl = "http://99design.co.il/";
var imagesToLoad = 20;
var imagesToLoadEveryTime = 20;
var imagesLoaded = 0;
var loading = false;
var watchDog = null;
var lastAppended = null;
var options = {
		minMargin: 25,
		resize: true,
		maxMargin: 25,
		itemSelector: ".catalogPage",
		lastRowClass: "last-row",
		firstItemClass: "first-item"};

$.fn.exists = function () {
    return this.length !== 0;
};

function loadTemplates() {  
	//$.template("pageTemplate", $("#pageResultTemplate").html());
}

function imageGrid() {
	$("#content").rowGrid(options);
}

function loadImages() {
	loading = true;
	
	var requestQuery = "php/search.php?q=chair+with+steel&l=" + imagesToLoadEveryTime + "&st=" + imagesLoaded;
	
	console.log(requestQuery);
	
	$.getJSON( requestQuery, function( data ) {
        $.each( data, function( key, page ) {
            {
				if (page.image == "")
				{
					imagesToLoad--;
					return; // this will continue the loop
				}
				
                // Page Link - http://99design.co.il/library/catalog/38/222
				$("#pageResultTemplate").tmpl({
                    "pageLink"       : hostUrl + "library/catalog/" + page.catalogID + "/" + page.catalogPageNum,
                    "catalogName"    : page.catalogDescription,
                    "sponsorName"    : page.sponsorName,
                    "image"          : hostUrl + "images/magazines/pages/s-" + page.image,
					"catalogImage"   : page.catalogImage,
					"pageNum"	     : page.catalogPageNum,
					"catalogID"      : page.catalogID,
					"sponsorPhone"   : page.sponsorPhone,
					"sponsorAddress" : (page.sponsorAddress).replace(/\\/g, ''),
					"pageID"         : page.pageID,
					"sponsorImage"   : page.sponsorImage
                }).appendTo( "#content" );
            }
        });
		
		addClickHandler();
		
		//If loading is over 2 seconds skip that image
		watchDog = setTimeout(function() {
			if (loading) {
				alert("Dog");
				imageGrid();
				loading = false;
			}
		}, 10000);

        // Get all the images
        $(".catalogPage .freshImage").each(function() {
            $(this).load(function(){
				$(this).removeClass("freshImage");
                imagesLoaded++;
                $(this).fadeIn(300);
                if(imagesLoaded == imagesToLoad)
                {
                    imageGrid();
					loading = false;
					if (watchDog != null)
					{
						clearTimeout(watchDog);
					}
                }
            });
        });
    });
}

function addClickHandler()
{
	$(".freshImage").parent().click(function(Event)
    {
        // The page div
        var thisObject = $(this).parent();
		
		var dataObject = thisObject.find(".dataDiv");

        // Centered offset = left offset - page wrapper - arrow width/2 - outer width/2;
        var leftOffset = thisObject.offset().left - 20 - 17 + thisObject.outerWidth() / 2;

        // Select the next .first-item (row start) and append the box before it.
        var nextRowObject = thisObject.nextAll( ".first-item, .last-row").slice(0, 1);

		var templateObject = {
				"pageLink" 	  		: hostUrl + "library/catalog/" + dataObject.data("catalog-id") + "/" + dataObject.data("catalog-page-num"),
				"sponsorName" 		: dataObject.data("sponsor-name"),
				"catalogName" 		: dataObject.data("catalog-name"),
				"pageNum"     		: dataObject.data("catalog-page-num"),
				"pageImage"  		: dataObject.data("page-image"),
				"catalogImage"		: hostUrl + "images/magazines/covers/" + dataObject.data("catalog-image"),
				"sponsorAddress"    : dataObject.data("sponsor-address"),
				"sponsorPhone"      : dataObject.data("sponsor-phone"),
				
                };
				
		if (dataObject.data("sponsor-image") != "")
		{
			templateObject.sponsorImage = hostUrl + "images/magazines/sponserslogos/" + dataObject.data("sponsor-image");
		}
		
        //Remove pageInfo element if exists
        $(".pageInfo").remove();
		

        if (nextRowObject.exists())
        {
			$.tmpl( $("#pageInfoTemplate").html(), templateObject).insertBefore( nextRowObject );
            //nextRowObject.before(htmlData)
        }
        else
        {
			$.tmpl( $("#pageInfoTemplate").html(), templateObject).appendTo( "#content" );
        }

        $(".topArrow").css({left: leftOffset});

        Event.preventDefault();
        return false;
    });
}

$(document).ready(function() {
	// Load Templates
	loadTemplates();

    // Load Search results rom url
	loadImages();
	
	// endless scrolling
	$(window).scroll(function() {
		if ($(window).scrollTop() + $(window).height() == $(document).height())
		{
			if (!loading) {
				imagesLoaded = imagesToLoad;
				imagesToLoad = imagesToLoad + imagesToLoadEveryTime;
				loadImages();
			}
		}
	});
});

function closePageInfo() {
    $(".pageInfo").remove();
}