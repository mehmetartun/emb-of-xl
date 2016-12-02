/**
 * Created by haseebriaz on 12/01/16.
 */

fin.desktop.main(function(){
   
	var activeWorkbook = null;
	var activeWorksheet = null;
	var allWorkbooks = null;
	var allWorksheets = null;
	
	
    var Excel = fin.desktop.Excel;
	

    Excel.init();
	console.log(Excel);
	alert("excel initialized");
    Excel.getConnectionStatus(isExcelConnected);
    Excel.addEventListener("connected", onExcelConnected);
	Excel.addEventListener("workbookClosed", function(event){
		console.log(event);
		//alert("he closed the workbook " + event.workbook.name);
		//var wb = model.getWorkbookName();
		//$("#excelstatus").html("Current Workbook is" + wb + " closed is " + event.workbook.name);
		getExistingWorkbook();
	});

    function isExcelConnected(isConnected){
		
        if(!isConnected){
            Excel.addEventListener("connected", onExcelConnected);
        } else {
            onExcelConnected();
        }
    }

    function onExcelConnected(){
		$("#excelconnectionstatus").html("Connection Established");
        getExistingWorkbook();
    }

    function getExistingWorkbook(){
		$("#excelopenworkbooks").html("");
        var workbookName = activeWorkbook;
        if(workbookName){
            Excel.getWorkbooks(function(workbooks){
				allWorkbooks = workbooks;
               var workbook = null;
                for(var i = 0; i < workbooks.length; i++){
					$("#excelopenworkbooks").append("<li><a href='#' id='wb"+workbooks[i].name+"'>"+workbooks[i].name+"</a></li>");
	                workbooks[i].addEventListener("workbookActivated", onWorkbookActivated);
	                workbooks[i].addEventListener("sheetAdded", onWorksheetAdded);
	                workbooks[i].addEventListener("sheetRemoved", onWorksheetRemoved);
					
                    if(workbooks[i].name === workbookName){
                        workbook = workbooks[i];
                        break;
                    }
                }
                if(workbook){
                    workbook.getWorksheets(function(sheets){
						allWorksheets = sheets;
                        setWorksheet(sheets[0]);
                    });
                } else {
                    addWorkbook();
                }
            });
        } else {
            addWorkbook();
        }
    }



	function onWorkbookActivated(event){
		//console.log(event);
		$("#excelactiveworkbook").html(event.target.name);
		//alert(event.target.name+" is activated");
		activeWorkbook = event.target.name;
	}


    function addWorkbook(){
        Excel.addWorkbook(function(workbook){
            activeWorkbook = workbook.name;
			$("#excelactiveworkbook").html(activeWorkbook);
			Excel.getWorkbooks(function(workbooks){
				allWorkbooks = workbooks;
				
                for(var i = 0; i < workbooks.length; i++){
					alert(i);
					$("#excelopenworkbooks").append("<li><a href='#' id='wb"+workbooks[i].name+"' >"+workbooks[i].name+"</a></li>");
				}
				
                for(var i = 0; i < workbooks.length; i++){
	                workbooks[i].addEventListener("workbookActivated", onWorkbookActivated);
	                workbooks[i].addEventListener("sheetAdded", onWorksheetAdded);
	                workbooks[i].addEventListener("sheetRemoved", onWorksheetRemoved);
                }
				
			});
            workbook.getWorksheets(function(sheets){
				setWorksheet(sheets[0]);
            });
        });
    }
	
	function onWorksheetAdded(worksheet){
		
	}
	function onWorksheetRemoved(worksheet){
		
	}

    function setWorksheet(worksheet){
		activeWorksheet = worksheet;
    }
});
