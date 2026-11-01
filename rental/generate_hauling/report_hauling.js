document.addEventListener(
    "DOMContentLoaded",
    function(){

        /* =====================================================
           ELEMENTS
        ===================================================== */

        const customDateFields =
        document.getElementById(
            "generateHaulingCustomDateFields"
        );

        const previewButton =
        document.getElementById(
            "previewHaulingReport"
        );

                const dateFromInput = document.getElementById(
            "generateHaulingDateFrom"
        );

        const dateToInput = document.getElementById(
            "generateHaulingDateTo"
        );

        function updateHaulingReportDateRestrictions(){

            if(dateFromInput){

                dateFromInput.min = "";

                dateFromInput.max = dateToInput
                    ? dateToInput.value
                    : "";
            }

            if(dateToInput){

                dateToInput.min = dateFromInput
                    ? dateFromInput.value
                    : "";

                dateToInput.max = "";
            }
        }


        /* =====================================================
           CLOSE ALL REPORT DROPDOWNS
        ===================================================== */

        function closeAllHaulingReportDropdowns(
            exceptMenu = null
        ){

            document
            .querySelectorAll(
                ".generate-hauling-dropdown-menu"
            )
            .forEach(
                function(menu){

                    if(
                        menu !== exceptMenu
                    ){

                        menu.classList.remove(
                            "show"
                        );

                        const parent =
                        menu.closest(
                            ".generate-hauling-dropdown"
                        );

                        const button =
                        parent
                        ?
                        parent.querySelector(
                            ".generate-hauling-dropdown-btn"
                        )
                        :
                        null;

                        if(
                            button
                        ){

                            button.setAttribute(
                                "aria-expanded",
                                "false"
                            );

                        }

                    }

                }
            );

        }


        /* =====================================================
           SETUP CUSTOM DROPDOWN
        ===================================================== */

        function setupDropdown(
            btnId,
            menuId,
            textId,
            inputId
        ){

            const btn =
            document.getElementById(
                btnId
            );

            const menu =
            document.getElementById(
                menuId
            );

            const text =
            document.getElementById(
                textId
            );

            const input =
            document.getElementById(
                inputId
            );


            if(
                !btn
                ||
                !menu
                ||
                !text
                ||
                !input
            ){

                return;

            }


            btn.setAttribute(
                "aria-expanded",
                "false"
            );


            /* =============================================
               OPEN / CLOSE
            ============================================= */

            btn.addEventListener(
                "click",
                function(event){

                    event.preventDefault();

                    event.stopPropagation();


                    const isOpen =
                    menu.classList.contains(
                        "show"
                    );


                    closeAllHaulingReportDropdowns(
                        menu
                    );


                    if(
                        isOpen
                    ){

                        menu.classList.remove(
                            "show"
                        );

                        btn.setAttribute(
                            "aria-expanded",
                            "false"
                        );

                    }
                    else{

                        menu.classList.add(
                            "show"
                        );

                        btn.setAttribute(
                            "aria-expanded",
                            "true"
                        );

                    }

                }
            );


            /* =============================================
               SELECT ITEM
            ============================================= */

            menu
            .querySelectorAll(
                ".generate-hauling-dropdown-item"
            )
            .forEach(
                function(item){

                    item.addEventListener(
                        "click",
                        function(event){

                            event.preventDefault();

                            event.stopPropagation();


                            const selectedValue =
                            String(
                                this.dataset.value
                                ||
                                ""
                            );


                            input.value =
                            selectedValue;


                            text.textContent =
                            this.textContent.trim();


                            menu.classList.remove(
                                "show"
                            );


                            btn.setAttribute(
                                "aria-expanded",
                                "false"
                            );


                            /* =================================
                               DATE RANGE OPTION
                            ================================= */

                            if(
                                inputId
                                ===
                                "generateHaulingDateOption"
                            ){

                                if(customDateFields){

                                    customDateFields.style.display =
                                        selectedValue === "custom"
                                            ? "flex"
                                            : "none";
                                }

                                if(selectedValue !== "custom"){

                                    if(dateFromInput){
                                        dateFromInput.value = "";
                                    }

                                    if(dateToInput){
                                        dateToInput.value = "";
                                    }
                                }

                                updateHaulingReportDateRestrictions();
                            }

                        }
                    );

                }
            );

        }


        /* =====================================================
           INITIALIZE DROPDOWNS
        ===================================================== */

        setupDropdown(
            "generateHaulingAffiliateBtn",
            "generateHaulingAffiliateMenu",
            "generateHaulingAffiliateText",
            "generateHaulingAffiliate"
        );


        setupDropdown(
            "generateHaulingStatusBtn",
            "generateHaulingStatusMenu",
            "generateHaulingStatusText",
            "generateHaulingStatus"
        );


        setupDropdown(
            "generateHaulingRemarksBtn",
            "generateHaulingRemarksMenu",
            "generateHaulingRemarksText",
            "generateHaulingRemarks"
        );


        setupDropdown(
            "generateHaulingDateBtn",
            "generateHaulingDateMenu",
            "generateHaulingDateText",
            "generateHaulingDateOption"
        );

                if(dateFromInput){
            dateFromInput.addEventListener(
                "change",
                updateHaulingReportDateRestrictions
            );
        }

        if(dateToInput){
            dateToInput.addEventListener(
                "change",
                updateHaulingReportDateRestrictions
            );
        }

        updateHaulingReportDateRestrictions();


        /* =====================================================
           CLOSE DROPDOWNS WHEN CLICKING OUTSIDE
        ===================================================== */

        document.addEventListener(
            "click",
            function(){

                closeAllHaulingReportDropdowns();

            }
        );


        /* =====================================================
           PREVIEW REPORT
        ===================================================== */

        if(
            previewButton
        ){

            previewButton.addEventListener(
                "click",
                function(event){

                    event.preventDefault();


                    const affiliate =
                    document.getElementById(
                        "generateHaulingAffiliate"
                    )?.value
                    ||
                    "all";


                    const status =
                    document.getElementById(
                        "generateHaulingStatus"
                    )?.value
                    ||
                    "all";


                    const remarks =
                    document.getElementById(
                        "generateHaulingRemarks"
                    )?.value
                    ||
                    "all";


                    const dateOption =
                    document.getElementById(
                        "generateHaulingDateOption"
                    )?.value
                    ||
                    "all";


                    const fromInput =
                    document.getElementById(
                        "generateHaulingDateFrom"
                    );


                    const toInput =
                    document.getElementById(
                        "generateHaulingDateTo"
                    );


                    const from =
                    fromInput
                    ?
                    String(
                        fromInput.value
                        ||
                        ""
                    ).trim()
                    :
                    "";


                    const to =
                    toInput
                    ?
                    String(
                        toInput.value
                        ||
                        ""
                    ).trim()
                    :
                    "";


                    /* =========================================
                       CUSTOM DATE VALIDATION
                    ========================================= */

                    if(
                        dateOption
                        ===
                        "custom"
                    ){

                        if(
                            from === ""
                            ||
                            to === ""
                        ){

                            Swal.fire({

                                icon:
                                "warning",

                                title:
                                "Complete the Date Range",

                                text:
                                "Please select both From and To dates.",

                                confirmButtonText:
                                "OK"

                            });

                            return;

                        }


                        if(
                            from > to
                        ){

                            Swal.fire({

                                icon:
                                "warning",

                                title:
                                "Invalid Date Range",

                                text:
                                "The From date cannot be later than the To date.",

                                confirmButtonText:
                                "OK"

                            });

                            return;

                        }

                    }               


                    /* =========================================
                       BUILD REPORT URL
                    ========================================= */

                    const url =
                    "generate_hauling/report_hauling.php?"
                    +
                    "affiliate="
                    +
                    encodeURIComponent(
                        affiliate
                    )
                    +
                    "&status="
                    +
                    encodeURIComponent(
                        status
                    )
                    +
                    "&remarks="
                    +
                    encodeURIComponent(
                        remarks
                    )
                    +
                    "&dateOption="
                    +
                    encodeURIComponent(
                        dateOption
                    )
                    +
                    "&from="
                    +
                    encodeURIComponent(
                        from
                    )
                    +
                    "&to="
                    +
                    encodeURIComponent(
                        to
                    );


                    /* =========================================
                       OPEN REPORT PREVIEW
                    ========================================= */

                    window.open(
                        url,
                        "_blank"
                    );

                }
            );

        }

    }
);