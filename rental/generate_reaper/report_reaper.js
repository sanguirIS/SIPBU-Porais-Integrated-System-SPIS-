document.addEventListener(
    "DOMContentLoaded",
    function(){

        /* ======================================================
           CUSTOM DROPDOWN
        ====================================================== */

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


            btn.addEventListener(
                "click",
                function(e){

                    e.preventDefault();
                    e.stopPropagation();


                    document
                    .querySelectorAll(
                        ".generate-reaper-dropdown-menu"
                    )
                    .forEach(
                    function(drop){

                        if(
                            drop !==
                            menu
                        ){
                            drop
                            .classList
                            .remove(
                                "show"
                            );
                        }

                    });


                    menu
                    .classList
                    .toggle(
                        "show"
                    );

                }
            );


            menu
            .querySelectorAll(
                ".generate-reaper-dropdown-item"
            )
            .forEach(
            function(item){

                item.addEventListener(
                    "click",
                    function(e){

                        e.preventDefault();
                        e.stopPropagation();


                        if(
                            this.dataset.disabled
                            ===
                            "1"
                        ){
                            return;
                        }


                        input.value =
                        this.dataset.value;


                        text.textContent =
                        this.textContent
                        .trim();


                        menu
                        .classList
                        .remove(
                            "show"
                        );


                        if(
                            inputId ===
                            "generateReaperDateOption"
                        ){

                            const customFields =
                            document.getElementById(
                                "generateReaperCustomDateFields"
                            );


                            if(
                                customFields
                            ){

                                customFields.style.display =
                                this.dataset.value ===
                                "custom"
                                ?
                                "flex"
                                :
                                "none";

                            }

                        }

                    }
                );

            });

        }


        /* ======================================================
           SETUP DROPDOWNS
        ====================================================== */

        setupDropdown(
            "generateReaperAffiliateBtn",
            "generateReaperAffiliateMenu",
            "generateReaperAffiliateText",
            "generateReaperAffiliate"
        );


        setupDropdown(
            "generateReaperStatusBtn",
            "generateReaperStatusMenu",
            "generateReaperStatusText",
            "generateReaperStatus"
        );


        setupDropdown(
            "generateReaperRemarksBtn",
            "generateReaperRemarksMenu",
            "generateReaperRemarksText",
            "generateReaperRemarks"
        );
        
        setupDropdown(
            "generateReaperDateBtn",
            "generateReaperDateMenu",
            "generateReaperDateText",
            "generateReaperDateOption"
        );


        /* ======================================================
           REAPER REPORT CUSTOM DATE RANGE
           Past and future rental dates are allowed.
        ====================================================== */

        const reaperReportFromInput =
        document.getElementById("generateReaperDateFrom");

        const reaperReportToInput =
        document.getElementById("generateReaperDateTo");

        function updateReaperReportDateRestrictions(){

            if(reaperReportFromInput){
                reaperReportFromInput.max =
                    reaperReportToInput?.value || "";
            }

            if(reaperReportToInput){
                reaperReportToInput.min =
                    reaperReportFromInput?.value || "";
            }
        }

        reaperReportFromInput?.addEventListener(
            "change",
            updateReaperReportDateRestrictions
        );

        reaperReportToInput?.addEventListener(
            "change",
            updateReaperReportDateRestrictions
        );

        updateReaperReportDateRestrictions();


        /* ======================================================
        REPORT STATUS / PAYMENT RULES

        Status must be selected first.

        Pending     = Unpaid only
        Ongoing     = Unpaid only
        Maintenance = Unpaid only

        Done        = Unpaid / Paid
        All Status  = All Remarks / Unpaid / Paid
        ====================================================== */

        function setGenerateReaperRemarksEnabled(
            enabled
        ){

            const remarksButton =
            document.getElementById(
                "generateReaperRemarksBtn"
            );

            const remarksMenu =
            document.getElementById(
                "generateReaperRemarksMenu"
            );


            if(
                !remarksButton
            ){
                return;
            }


            remarksButton.disabled =
            !enabled;


            remarksButton.setAttribute(
                "aria-disabled",
                enabled
                ?
                "false"
                :
                "true"
            );


            if(
                !enabled
            ){

                remarksMenu
                ?.classList
                .remove(
                    "show"
                );

            }

        }


        function applyGenerateReaperStatusFirstRules(){

            const statusInput =
            document.getElementById(
                "generateReaperStatus"
            );

            const remarksInput =
            document.getElementById(
                "generateReaperRemarks"
            );

            const remarksText =
            document.getElementById(
                "generateReaperRemarksText"
            );


            if(
                !statusInput
                ||
                !remarksInput
                ||
                !remarksText
            ){
                return;
            }


            const selectedStatus =
            String(
                statusInput.value
                ||
                ""
            );


            /* No Status selected yet */

            if(
                selectedStatus === ""
            ){

                remarksInput.value =
                "all";


                remarksText.textContent =
                "Select Status First";


                setGenerateReaperRemarksEnabled(
                    false
                );


                return;

            }


            /* Pending / Ongoing / Maintenance = Unpaid only */

            if(
                [
                    "Pending",
                    "Ongoing",
                    "Maintenance"
                ]
                .includes(
                    selectedStatus
                )
            ){

                remarksInput.value =
                "Unpaid";


                remarksText.textContent =
                "Unpaid";


                setGenerateReaperRemarksEnabled(
                    false
                );


                return;

            }


            /* Done / All Status */

            remarksInput.value =
            "all";


            remarksText.textContent =
            "All Remarks";


            setGenerateReaperRemarksEnabled(
                true
            );

        }


        /* ======================================================
        STATUS CHANGE
        ====================================================== */

        document
        .querySelectorAll(
            "#generateReaperStatusMenu .generate-reaper-dropdown-item"
        )
        .forEach(
        function(item){

            item.addEventListener(
                "click",
                function(){

                    applyGenerateReaperStatusFirstRules();

                }
            );

        });


        /*
        Apply immediately when modal/page loads.
        */

        applyGenerateReaperStatusFirstRules();


        /* ======================================================
           CLOSE DROPDOWNS
        ====================================================== */

        document.addEventListener(
            "click",
            function(){

                document
                .querySelectorAll(
                    ".generate-reaper-dropdown-menu"
                )
                .forEach(
                function(menu){

                    menu
                    .classList
                    .remove(
                        "show"
                    );

                });

            }
        );


        /* ======================================================
           PREVIEW REPORT
        ====================================================== */

        const previewButton =
        document.getElementById(
            "previewReaperReport"
        );


        if(
            previewButton
        ){

            previewButton
            .addEventListener(
                "click",
                function(){

                    const affiliate =
                    document
                    .getElementById(
                        "generateReaperAffiliate"
                    )
                    ?.value
                    ||
                    "all";


                    const status =
                    document
                    .getElementById(
                        "generateReaperStatus"
                    )
                    ?.value
                    ||
                    "";


                    if(
                        status === ""
                    ){

                        if(
                            typeof Swal !==
                            "undefined"
                        ){

                            Swal.fire({
                                icon:"warning",
                                title:"Select Status",
                                text:"Please select a Status before previewing the report.",
                                confirmButtonText:"OK",
                                confirmButtonColor:"#8CA15B"
                            });

                        }
                        else{

                            window.alert(
                                "Please select a Status before previewing the report."
                            );

                        }


                        document
                        .getElementById(
                            "generateReaperStatusBtn"
                        )
                        ?.focus();


                        return;

                    }


                    let remarks =
                    document
                    .getElementById(
                        "generateReaperRemarks"
                    )
                    ?.value
                    ||
                    "all";


                    /*
                    Final safeguard in JS.

                    Pending / Ongoing / Maintenance
                    can never request Paid records.
                    */

                    if(
                        [
                            "Pending",
                            "Ongoing",
                            "Maintenance"
                        ]
                        .includes(
                            status
                        )
                    ){

                        remarks =
                        "Unpaid";

                    }


                    const dateOption =
                    document
                    .getElementById(
                        "generateReaperDateOption"
                    )
                    ?.value
                    ||
                    "all";


                    const from =
                    document
                    .getElementById(
                        "generateReaperDateFrom"
                    )
                    ?.value
                    ||
                    "";


                    const to =
                    document
                    .getElementById(
                        "generateReaperDateTo"
                    )
                    ?.value
                    ||
                    "";

                    /* ======================================================
                       CUSTOM DATE VALIDATION
                    ====================================================== */

                    if(dateOption === "custom"){

                        if(!from || !to){

                            Swal.fire({
                                icon: "warning",
                                title: "Incomplete Date Range",
                                text: "Please select both From and To dates.",
                                confirmButtonColor: "#8CA15B"
                            });

                            return;
                        }

                        if(from > to){

                            Swal.fire({
                                icon: "warning",
                                title: "Invalid Date Range",
                                text: "The From date cannot be later than the To date.",
                                confirmButtonColor: "#8CA15B"
                            });

                            return;
                        }
                    }


                    const url =
                    "generate_reaper/report_reaper.php?"
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


                    window.open(
                        url,
                        "_blank"
                    );

                }
            );

        }

    }
);