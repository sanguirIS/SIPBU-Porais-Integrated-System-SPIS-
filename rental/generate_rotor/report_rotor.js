document.addEventListener(
    "DOMContentLoaded",
    () => {

                const rotorReportFromInput = document.getElementById(
            "generateRotorDateFrom"
        );

        const rotorReportToInput = document.getElementById(
            "generateRotorDateTo"
        );

        function updateRotorReportDateRestrictions(){

            if(rotorReportFromInput){
                rotorReportFromInput.min = "";
                rotorReportFromInput.max = rotorReportToInput
                    ? rotorReportToInput.value
                    : "";
            }

            if(rotorReportToInput){
                rotorReportToInput.min = rotorReportFromInput
                    ? rotorReportFromInput.value
                    : "";
                rotorReportToInput.max = "";
            }
        }

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
                function(event){

                    event.stopPropagation();


                    document
                    .querySelectorAll(
                        ".generate-rotor-dropdown-menu"
                    )
                    .forEach(drop=>{

                        if(
                            drop !== menu
                        ){

                            drop.classList.remove(
                                "show"
                            );

                        }

                    });


                    menu.classList.toggle(
                        "show"
                    );

                }
            );


            menu
            .querySelectorAll(
                ".generate-rotor-dropdown-item"
            )
            .forEach(item=>{

                item.addEventListener(
                    "click",
                    function(){

                        if(
                            this.dataset.disabled ===
                            "1"
                        ){

                            return;

                        }


                        input.value =
                        this.dataset.value;


                        text.textContent =
                        this.textContent.trim();


                        menu.classList.remove(
                            "show"
                        );


                        if(
                            inputId ===
                            "generateRotorDateOption"
                        ){

                            const customFields =
                            document.getElementById(
                                "generateRotorCustomDateFields"
                            );


                            if(customFields){

                                customFields.style.display =

                                this.dataset.value ===
                                "custom"

                                ?

                                "flex"

                                :

                                "none";

                            }

                            if(this.dataset.value !== "custom"){

                                if(rotorReportFromInput){
                                    rotorReportFromInput.value = "";
                                }

                                if(rotorReportToInput){
                                    rotorReportToInput.value = "";
                                }
                            }

                            updateRotorReportDateRestrictions();

                        }

                    }
                );

            });

        }


        setupDropdown(
            "generateRotorAffiliateBtn",
            "generateRotorAffiliateMenu",
            "generateRotorAffiliateText",
            "generateRotorAffiliate"
        );


        setupDropdown(
            "generateRotorStatusBtn",
            "generateRotorStatusMenu",
            "generateRotorStatusText",
            "generateRotorStatus"
        );

        setupDropdown(
            "generateRotorDateBtn",
            "generateRotorDateMenu",
            "generateRotorDateText",
            "generateRotorDateOption"
        );


        setupDropdown(
            "generateRotorRemarksBtn",
            "generateRotorRemarksMenu",
            "generateRotorRemarksText",
            "generateRotorRemarks"
        );


        if(rotorReportFromInput){
            rotorReportFromInput.addEventListener(
                "change",
                updateRotorReportDateRestrictions
            );
        }

        if(rotorReportToInput){
            rotorReportToInput.addEventListener(
                "change",
                updateRotorReportDateRestrictions
            );
        }

        updateRotorReportDateRestrictions();

        document.addEventListener(
            "click",
            function(){

                document
                .querySelectorAll(
                    ".generate-rotor-dropdown-menu"
                )
                .forEach(menu=>{

                    menu.classList.remove(
                        "show"
                    );

                });

            }
        );

    }
);


document
.getElementById(
    "previewRotorReport"
)
?.addEventListener(
    "click",
    function(){

        const affiliate =
        document.getElementById(
            "generateRotorAffiliate"
        ).value;


        const status =
        document.getElementById(
            "generateRotorStatus"
        ).value;


        const remarks =
        document.getElementById(
            "generateRotorRemarks"
        ).value;


        const dateOption =
        document.getElementById(
            "generateRotorDateOption"
        ).value;


        const from =
        document.getElementById(
            "generateRotorDateFrom"
        ).value;


        const to =
        document.getElementById(
            "generateRotorDateTo"
        ).value;


        /* ======================================================
        CUSTOM DATE VALIDATION
        ====================================================== */

        if(
            dateOption ===
            "custom"
        ){

            if(
                !from
                ||
                !to
            ){

                Swal.fire({

                    icon:
                    "warning",

                    title:
                    "Incomplete Date Range",

                    text:
                    "Please select both From and To dates.",

                    confirmButtonColor:
                    "#8CA15B"

                });


                return;

            }


            if(
                from >
                to
            ){

                Swal.fire({

                    icon:
                    "warning",

                    title:
                    "Invalid Date Range",

                    text:
                    "The From date cannot be later than the To date.",

                    confirmButtonColor:
                    "#8CA15B"

                });


                return;

            }

        }


        let url =
        "generate_rotor/report_rotor.php?"
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