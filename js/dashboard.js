/* =========================================================
   SPIS DASHBOARD
   File: /spis/js/dashboard.js
========================================================= */

document.addEventListener("DOMContentLoaded", function () {
    "use strict";


    /* =====================================================
       DASHBOARD PAGE
    ===================================================== */

    const dashboardPage =
        document.getElementById("dashboardPage");

    if (!dashboardPage) {
        return;
    }


    /* =====================================================
       HELPERS
    ===================================================== */

    function parseJsonArray(value) {
        try {
            const parsed = JSON.parse(value || "[]");

            return Array.isArray(parsed)
                ? parsed
                : [];
        } catch (error) {
            console.error(
                "Dashboard JSON parsing error:",
                error
            );

            return [];
        }
    }


    function formatNumber(value) {
        return new Intl.NumberFormat(
            "en-PH",
            {
                maximumFractionDigits: 0
            }
        ).format(Number(value) || 0);
    }


    function formatMoney(value) {
        return new Intl.NumberFormat(
            "en-PH",
            {
                style: "currency",
                currency: "PHP",
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        ).format(Number(value) || 0);
    }


    async function showValidationAlert(
        title,
        message
    ) {
        try {
            if (typeof window.Swal !== "undefined") {
                await window.Swal.fire({
                    icon: "warning",
                    title: title,
                    text: message,
                    confirmButtonText: "OK",
                    confirmButtonColor: "#8da758",
                    allowOutsideClick: false
                });

                return;
            }
        } catch (error) {
            console.warn(
                "SweetAlert could not be displayed:",
                error
            );
        }

        window.alert(message);
    }


    /* =====================================================
       SCROLL RESTORATION
    ===================================================== */

    const SCROLL_POSITION_KEY =
        "spis_dashboard_scroll_position";

    const RESTORE_SCROLL_KEY =
        "spis_dashboard_restore_scroll";


    function saveDashboardScroll() {
        try {
            sessionStorage.setItem(
                SCROLL_POSITION_KEY,
                String(
                    Math.max(
                        0,
                        dashboardPage.scrollTop
                    )
                )
            );
        } catch (error) {
            console.warn(
                "Unable to save dashboard scroll:",
                error
            );
        }
    }


    function markScrollForRestore() {
        try {
            sessionStorage.setItem(
                RESTORE_SCROLL_KEY,
                "1"
            );
        } catch (error) {
            console.warn(
                "Unable to mark dashboard scroll:",
                error
            );
        }
    }


    function isBrowserReload() {
        try {
            const entries =
                performance.getEntriesByType(
                    "navigation"
                );

            if (entries.length > 0) {
                return entries[0].type === "reload";
            }
        } catch (error) {
            return false;
        }

        return false;
    }


    function shouldRestoreScroll() {
        let filterReload = false;

        try {
            filterReload =
                sessionStorage.getItem(
                    RESTORE_SCROLL_KEY
                ) === "1";
        } catch (error) {
            filterReload = false;
        }

        return (
            filterReload ||
            isBrowserReload()
        );
    }


    function restoreDashboardScroll() {
        if (!shouldRestoreScroll()) {
            return;
        }

        let savedPosition = 0;

        try {
            savedPosition = Number(
                sessionStorage.getItem(
                    SCROLL_POSITION_KEY
                ) || "0"
            );
        } catch (error) {
            savedPosition = 0;
        }


        if (
            !Number.isFinite(savedPosition) ||
            savedPosition < 0
        ) {
            savedPosition = 0;
        }


        requestAnimationFrame(
            function () {
                requestAnimationFrame(
                    function () {
                        dashboardPage.scrollTop =
                            savedPosition;

                        try {
                            sessionStorage.removeItem(
                                RESTORE_SCROLL_KEY
                            );
                        } catch (error) {
                            // Ignore storage cleanup error.
                        }
                    }
                );
            }
        );
    }


    let scrollFrame = null;

    dashboardPage.addEventListener(
        "scroll",
        function () {
            if (scrollFrame !== null) {
                return;
            }

            scrollFrame =
                requestAnimationFrame(
                    function () {
                        saveDashboardScroll();
                        scrollFrame = null;
                    }
                );
        },
        {
            passive: true
        }
    );


    window.addEventListener(
        "pagehide",
        function () {
            saveDashboardScroll();
        }
    );


    restoreDashboardScroll();


    /* =====================================================
       NORMAL RANGE BUTTONS
    ===================================================== */

    document
        .querySelectorAll(
            ".dashboard-range-btn"
        )
        .forEach(function (button) {
            button.addEventListener(
                "click",
                function () {
                    saveDashboardScroll();
                    markScrollForRestore();
                }
            );
        });


    /* =====================================================
       CUSTOM RANGE ELEMENTS
    ===================================================== */

    const filterForm =
        document.getElementById(
            "dashboardFilterForm"
        );

    const customRangeButton =
        document.getElementById(
            "dashboardCustomRangeBtn"
        );

    const customRangePanel =
        document.getElementById(
            "dashboardCustomRangePanel"
        );

    const fromDateInput =
        document.getElementById(
            "dashboardFromDate"
        );

    const toDateInput =
        document.getElementById(
            "dashboardToDate"
        );


    let fromDatePicker = null;
    let toDatePicker = null;


    /* =====================================================
       CUSTOM RANGE PANEL
       IMPORTANT:
       Button behavior is attached BEFORE Flatpickr.
       So even if Flatpickr fails, button still works.
    ===================================================== */

    function openNativePicker(input) {
        if (!input) {
            return;
        }

        input.focus();

        try {
            if (
                typeof input.showPicker ===
                "function"
            ) {
                input.showPicker();
            }
        } catch (error) {
            /*
             * Some browsers require showPicker()
             * to happen directly from user interaction.
             * The input is still focused and clickable.
             */
        }
    }


    function openFromCalendar() {
        if (fromDatePicker) {
            try {
                fromDatePicker.open();
                return;
            } catch (error) {
                console.warn(
                    "Flatpickr From calendar failed:",
                    error
                );
            }
        }

        openNativePicker(fromDateInput);
    }


    function openToCalendar() {
        if (toDatePicker) {
            try {
                toDatePicker.open();
                return;
            } catch (error) {
                console.warn(
                    "Flatpickr To calendar failed:",
                    error
                );
            }
        }

        openNativePicker(toDateInput);
    }


    function closeDatePickers() {
        if (fromDatePicker) {
            try {
                fromDatePicker.close();
            } catch (error) {
                // Ignore.
            }
        }

        if (toDatePicker) {
            try {
                toDatePicker.close();
            } catch (error) {
                // Ignore.
            }
        }
    }


    function openCustomRangePanel(
        openCalendar = false
    ) {
        if (!customRangePanel) {
            return;
        }

        customRangePanel.hidden = false;


        if (customRangeButton) {
            customRangeButton.classList.add(
                "active"
            );

            customRangeButton.setAttribute(
                "aria-expanded",
                "true"
            );
        }


        if (openCalendar) {
            openFromCalendar();
        }
    }


    function closeCustomRangePanel() {
        if (!customRangePanel) {
            return;
        }

        closeDatePickers();

        customRangePanel.hidden = true;


        if (customRangeButton) {
            customRangeButton.classList.remove(
                "active"
            );

            customRangeButton.setAttribute(
                "aria-expanded",
                "false"
            );
        }
    }


    /* =====================================================
       BIND CUSTOM RANGE BUTTON FIRST
    ===================================================== */

    if (
        customRangeButton &&
        customRangePanel
    ) {
        customRangeButton.setAttribute(
            "aria-controls",
            "dashboardCustomRangePanel"
        );

        customRangeButton.setAttribute(
            "aria-expanded",
            customRangePanel.hidden
                ? "false"
                : "true"
        );


        customRangeButton.addEventListener(
            "click",
            function (event) {
                event.preventDefault();
                event.stopPropagation();


                if (customRangePanel.hidden) {
                    openCustomRangePanel(true);
                } else {
                    closeCustomRangePanel();
                }
            }
        );


        customRangePanel.addEventListener(
            "click",
            function (event) {
                event.stopPropagation();
            }
        );


        document.addEventListener(
            "click",
            function (event) {
                const target = event.target;

                if (!(target instanceof Node)) {
                    return;
                }


                const clickedButton =
                    customRangeButton.contains(
                        target
                    );

                const clickedPanel =
                    customRangePanel.contains(
                        target
                    );

                const clickedCalendar =
                    target instanceof Element &&
                    Boolean(
                        target.closest(
                            ".flatpickr-calendar"
                        )
                    );


                if (
                    !clickedButton &&
                    !clickedPanel &&
                    !clickedCalendar
                ) {
                    closeCustomRangePanel();
                }
            }
        );


        document.addEventListener(
            "keydown",
            function (event) {
                if (
                    event.key === "Escape" &&
                    !customRangePanel.hidden
                ) {
                    closeCustomRangePanel();
                }
            }
        );
    }


    /* =====================================================
       FLATPICKR
       OPTIONAL ENHANCEMENT ONLY

       Even if this section completely fails,
       Custom Range still remains usable because
       native <input type="date"> is our fallback.
    ===================================================== */

    if (
        fromDateInput &&
        toDateInput &&
        typeof window.flatpickr === "function"
    ) {
        try {
            /*
             * Flatpickr works more consistently when
             * the source input is text.
             *
             * Since the PHP uses type="date" for a
             * fail-safe native calendar, convert it
             * only after confirming Flatpickr exists.
             */

            fromDateInput.type = "text";
            toDateInput.type = "text";


            const commonOptions = {
                dateFormat: "Y-m-d",

                altInput: true,
                altFormat: "F j, Y",

                maxDate: "today",

                disableMobile: true,
                allowInput: false,

                clickOpens: true,

                appendTo:
                    customRangePanel
            };


            /* =========================
               FROM DATE
            ========================= */

            fromDatePicker =
                window.flatpickr(
                    fromDateInput,
                    {
                        ...commonOptions,

                        onChange: function (
                            selectedDates
                        ) {
                            if (
                                selectedDates.length ===
                                0
                            ) {
                                return;
                            }


                            if (toDatePicker) {
                                toDatePicker.set(
                                    "minDate",
                                    selectedDates[0]
                                );
                            }


                            if (
                                fromDateInput.value &&
                                toDateInput.value &&
                                fromDateInput.value >
                                toDateInput.value
                            ) {
                                if (toDatePicker) {
                                    toDatePicker.clear();
                                } else {
                                    toDateInput.value =
                                        "";
                                }
                            }


                            /*
                             * Automatically continue
                             * to the ending date.
                             */

                            window.setTimeout(
                                function () {
                                    openToCalendar();
                                },
                                100
                            );
                        }
                    }
                );


            /* =========================
               TO DATE
            ========================= */

            toDatePicker =
                window.flatpickr(
                    toDateInput,
                    {
                        ...commonOptions,

                        minDate:
                            fromDateInput.value ||
                            null,

                        onChange: function (
                            selectedDates
                        ) {
                            if (
                                selectedDates.length ===
                                0
                            ) {
                                return;
                            }


                            if (fromDatePicker) {
                                fromDatePicker.set(
                                    "maxDate",
                                    selectedDates[0]
                                );
                            }
                        }
                    }
                );

        } catch (error) {
            console.error(
                "Dashboard Flatpickr initialization failed:",
                error
            );


            /*
             * Destroy partially-created Flatpickr
             * instances if necessary.
             */

            try {
                if (fromDatePicker) {
                    fromDatePicker.destroy();
                }
            } catch (destroyError) {
                // Ignore.
            }

            try {
                if (toDatePicker) {
                    toDatePicker.destroy();
                }
            } catch (destroyError) {
                // Ignore.
            }


            fromDatePicker = null;
            toDatePicker = null;


            /*
             * Restore native browser calendars.
             */

            fromDateInput.type = "date";
            toDateInput.type = "date";
        }
    }


    /* =====================================================
       NATIVE DATE FALLBACK RULES
    ===================================================== */

    if (
        fromDateInput &&
        toDateInput
    ) {
        fromDateInput.addEventListener(
            "change",
            function () {
                const fromDate =
                    String(
                        fromDateInput.value || ""
                    );

                if (
                    !fromDatePicker &&
                    fromDate
                ) {
                    toDateInput.min =
                        fromDate;


                    if (
                        toDateInput.value &&
                        toDateInput.value <
                        fromDate
                    ) {
                        toDateInput.value = "";
                    }


                    openToCalendar();
                }
            }
        );


        toDateInput.addEventListener(
            "change",
            function () {
                const toDate =
                    String(
                        toDateInput.value || ""
                    );

                if (
                    !toDatePicker &&
                    toDate
                ) {
                    fromDateInput.max =
                        toDate;
                }
            }
        );
    }


    /* =====================================================
       CUSTOM RANGE VALIDATION
    ===================================================== */

    if (
        filterForm &&
        fromDateInput &&
        toDateInput
    ) {
        filterForm.addEventListener(
            "submit",
            async function (event) {
                const fromDate =
                    String(
                        fromDateInput.value || ""
                    ).trim();

                const toDate =
                    String(
                        toDateInput.value || ""
                    ).trim();


                /* EMPTY */

                if (!fromDate || !toDate) {
                    event.preventDefault();

                    await showValidationAlert(
                        "Incomplete date range",
                        "Please select both the starting and ending date."
                    );


                    openCustomRangePanel(false);


                    if (!fromDate) {
                        openFromCalendar();
                    } else {
                        openToCalendar();
                    }

                    return;
                }


                /* INVALID ORDER */

                if (fromDate > toDate) {
                    event.preventDefault();

                    await showValidationAlert(
                        "Invalid date range",
                        "The starting date cannot be later than the ending date."
                    );


                    openCustomRangePanel(false);
                    openFromCalendar();

                    return;
                }


                /*
                 * Valid submit.
                 * Save current page position.
                 */

                saveDashboardScroll();
                markScrollForRestore();
            }
        );
    }

    /* =====================================================
   DASHBOARD CHART DATA
===================================================== */

const salesAccess =
    String(
        dashboardPage.dataset
            .salesAccess || "0"
    ) === "1";


/* =====================================================
   PIE CHART DATA
===================================================== */

const chartCanvas =
    document.getElementById(
        "dashboardSalesChart"
    );

const chartFallback =
    document.getElementById(
        "dashboardChartFallback"
    );


const chartLabels =
    parseJsonArray(
        dashboardPage.dataset
            .chartLabels
    ).map(function (label) {
        return String(label);
    });


const chartValues =
    parseJsonArray(
        dashboardPage.dataset
            .chartValues
    ).map(function (value) {
        return Number(value) || 0;
    });


const chartAmounts =
    parseJsonArray(
        dashboardPage.dataset
            .chartAmounts
    ).map(function (value) {
        return Number(value) || 0;
    });


/* =====================================================
   SALES TREND DATA
===================================================== */

const trendCanvas =
    document.getElementById(
        "dashboardSalesTrendChart"
    );

const trendFallback =
    document.getElementById(
        "dashboardTrendFallback"
    );


const trendLabels =
    parseJsonArray(
        dashboardPage.dataset
            .trendLabels
    ).map(function (label) {
        return String(label);
    });


const trendValues =
    parseJsonArray(
        dashboardPage.dataset
            .trendValues
    ).map(function (value) {
        return Number(value) || 0;
    });


const trendGrouping =
    String(
        dashboardPage.dataset
            .trendGrouping || "day"
    );


/* =====================================================
   PIE CHART COLORS
===================================================== */

const chartColors = [
    "#8da758",
    "#d84b55",
    "#76afe0",
    "#2ca064",
    "#b954b6",
    "#575ab9",
    "#f5c63e",
    "#ee8f49"
];


document
    .querySelectorAll(
        "[data-chart-color-index]"
    )
    .forEach(function (colorDot) {

        const colorIndex =
            Number(
                colorDot.getAttribute(
                    "data-chart-color-index"
                )
            );


        if (
            !Number.isFinite(
                colorIndex
            )
        ) {
            return;
        }


        colorDot.style.backgroundColor =
            chartColors[
                colorIndex %
                chartColors.length
            ];
    });


/* =====================================================
   FALLBACK HELPERS
===================================================== */

function showPieFallback(message) {

    if (chartCanvas) {
        chartCanvas.hidden = true;
    }


    if (chartFallback) {

        chartFallback.textContent =
            message;

        chartFallback.hidden =
            false;
    }
}


function hidePieFallback() {

    if (chartFallback) {
        chartFallback.hidden = true;
    }


    if (chartCanvas) {
        chartCanvas.hidden = false;
    }
}


function showTrendFallback(message) {

    if (trendCanvas) {
        trendCanvas.hidden = true;
    }


    if (trendFallback) {

        trendFallback.textContent =
            message;

        trendFallback.hidden =
            false;
    }
}


function hideTrendFallback() {

    if (trendFallback) {
        trendFallback.hidden = true;
    }


    if (trendCanvas) {
        trendCanvas.hidden = false;
    }
}


/* =====================================================
   CHART.JS AVAILABILITY
===================================================== */

const chartLibraryAvailable =
    typeof window.Chart !==
    "undefined";


if (!salesAccess) {

    showPieFallback(
        "Sales access is required to view this chart."
    );

    showTrendFallback(
        "Sales access is required to view this chart."
    );

} else if (!chartLibraryAvailable) {

    showPieFallback(
        "The offline chart library could not be loaded."
    );

    showTrendFallback(
        "The offline chart library could not be loaded."
    );

} else {

    /* =================================================
       PRODUCT SALES SHARE PIE CHART
    ================================================= */

    const totalChartUnits =
        chartValues.reduce(
            function (
                total,
                value
            ) {

                return (
                    total +
                    (
                        Number(value) ||
                        0
                    )
                );
            },
            0
        );


    if (
        !chartCanvas ||
        chartLabels.length === 0 ||
        chartValues.length === 0 ||
        totalChartUnits <= 0
    ) {

        showPieFallback(
            "No chart data is available for this period."
        );

    } else {

        hidePieFallback();


        const chartContext =
            chartCanvas.getContext(
                "2d"
            );


        if (!chartContext) {

            showPieFallback(
                "The chart could not be displayed."
            );

        } else {

            try {

                new window.Chart(
                    chartContext,
                    {
                        type: "pie",

                        data: {

                            labels:
                                chartLabels,

                            datasets: [
                                {
                                    data:
                                        chartValues,

                                    backgroundColor:
                                        chartLabels.map(
                                            function (
                                                label,
                                                index
                                            ) {

                                                return chartColors[
                                                    index %
                                                    chartColors.length
                                                ];
                                            }
                                        ),

                                    borderColor:
                                        "#fffef8",

                                    borderWidth:
                                        4,

                                    hoverBorderColor:
                                        "#fffef8",

                                    hoverBorderWidth:
                                        5,

                                    hoverOffset:
                                        10
                                }
                            ]
                        },

                        options: {

                            responsive:
                                true,

                            maintainAspectRatio:
                                false,

                            animation: {
                                duration:
                                    700,

                                easing:
                                    "easeOutQuart"
                            },

                            layout: {
                                padding:
                                    13
                            },

                            plugins: {

                                legend: {
                                    display:
                                        false
                                },

                                tooltip: {

                                    displayColors:
                                        true,

                                    backgroundColor:
                                        "rgba(30, 43, 18, 0.94)",

                                    titleColor:
                                        "#ffffff",

                                    bodyColor:
                                        "#ffffff",

                                    padding:
                                        12,

                                    cornerRadius:
                                        9,

                                    callbacks: {

                                        title:
                                            function (
                                                items
                                            ) {

                                                return (
                                                    items.length >
                                                    0
                                                )
                                                    ? items[0]
                                                        .label
                                                    : "";
                                            },

                                        label:
                                            function (
                                                context
                                            ) {

                                                const units =
                                                    Number(
                                                        context.raw
                                                    ) ||
                                                    0;


                                                const percentage =
                                                    totalChartUnits >
                                                    0
                                                        ? (
                                                            units /
                                                            totalChartUnits *
                                                            100
                                                        ).toFixed(
                                                            1
                                                        )
                                                        : "0.0";


                                                return (
                                                    " " +
                                                    formatNumber(
                                                        units
                                                    ) +
                                                    " units · " +
                                                    percentage +
                                                    "%"
                                                );
                                            },

                                        afterLabel:
                                            function (
                                                context
                                            ) {

                                                const amount =
                                                    chartAmounts[
                                                        context
                                                            .dataIndex
                                                    ] ||
                                                    0;


                                                return (
                                                    " Sales: " +
                                                    formatMoney(
                                                        amount
                                                    )
                                                );
                                            }
                                    }
                                }
                            }
                        }
                    }
                );

            } catch (error) {

                console.error(
                    "Dashboard pie chart error:",
                    error
                );


                showPieFallback(
                    "The chart could not be displayed."
                );
            }
        }
    }


    /* =================================================
       SALES TREND LINE CHART
    ================================================= */

    const hasTrendData =
        trendValues.some(
            function (value) {
                return (
                    Number(value) >
                    0
                );
            }
        );

    const useTrendBarChart =
        trendLabels.length === 1;  


    if (
        !trendCanvas ||
        trendLabels.length === 0 ||
        trendValues.length === 0
    ) {

        showTrendFallback(
            "No Sales trend data is available for this period."
        );

    } else {

        /*
         * Even if all values are zero,
         * still show the timeline.
         *
         * This is useful because zero sales
         * is still valid information.
         */

        hideTrendFallback();


        const trendContext =
            trendCanvas.getContext(
                "2d"
            );


        if (!trendContext) {

            showTrendFallback(
                "The Sales trend could not be displayed."
            );

        } else {

            try {

                const trendGradient =
                    trendContext
                        .createLinearGradient(
                            0,
                            0,
                            0,
                            320
                        );


                trendGradient.addColorStop(
                    0,
                    "rgba(141, 167, 88, 0.32)"
                );


                trendGradient.addColorStop(
                    1,
                    "rgba(141, 167, 88, 0.02)"
                );


                new window.Chart(
                    trendContext,
                    {
                        type:
                            useTrendBarChart
                                ? "bar"
                                : "line",

                        data: {

                            labels:
                                trendLabels,

                            datasets: [
                                {
                                    label:
                                        "Sales",

                                    data:
                                        trendValues,

                                    borderColor:
                                        "#78954a",

                                    backgroundColor:
                                        trendGradient,

                                    borderWidth:
                                        useTrendBarChart
                                            ? 1
                                            : 3,

                                    borderRadius:
                                        useTrendBarChart
                                            ? 16
                                            : 0,

                                    borderSkipped:
                                        false,

                                    maxBarThickness:
                                        useTrendBarChart
                                            ? 140
                                            : undefined,

                                    categoryPercentage:
                                        useTrendBarChart
                                            ? 0.55
                                            : undefined,

                                    barPercentage:
                                        useTrendBarChart
                                            ? 0.72
                                            : undefined,

                                    fill:
                                        !useTrendBarChart,

                                    tension:
                                        useTrendBarChart
                                            ? 0
                                            : 0.35,

                                    pointRadius:
                                        useTrendBarChart
                                            ? 0
                                            : (
                                                trendLabels.length > 35
                                                    ? 0
                                                    : 3
                                            ),

                                    pointHoverRadius:
                                        useTrendBarChart
                                            ? 0
                                            : 6,

                                    pointBackgroundColor:
                                        "#78954a",

                                    pointBorderColor:
                                        "#fffef8",

                                    pointBorderWidth:
                                        useTrendBarChart
                                            ? 0
                                            : 2
                                }
                            ]
                        },

                        options: {

                            responsive:
                                true,

                            maintainAspectRatio:
                                false,

                            interaction: {

                                mode:
                                    "index",

                                intersect:
                                    false
                            },

                            animation: {

                                duration:
                                    700,

                                easing:
                                    "easeOutQuart"
                            },

                            scales: {

                                x: {

                                    border: {
                                        display:
                                            false
                                    },

                                    grid: {
                                        display:
                                            false
                                    },

                                    ticks: {

                                        color:
                                            "#687060",

                                        font: {
                                            family:
                                                "Poppins",

                                            size:
                                                9,

                                            weight:
                                                "600"
                                        },

                                        maxRotation:
                                            0,

                                        autoSkip:
                                            true,

                                        maxTicksLimit:
                                            trendGrouping ===
                                            "hour"
                                                ? 8
                                                : 10
                                    }
                                },

                                y: {

                                    beginAtZero:
                                        true,

                                    grace:
                                        "12%",

                                    border: {
                                        display:
                                            false
                                    },

                                    grid: {

                                        color:
                                            "rgba(82, 104, 48, 0.11)",

                                        drawTicks:
                                            false
                                    },

                                    ticks: {

                                        color:
                                            "#687060",

                                        padding:
                                            9,

                                        font: {
                                            family:
                                                "Poppins",

                                            size:
                                                9,

                                            weight:
                                                "600"
                                        },

                                        callback:
                                            function (
                                                value
                                            ) {

                                                const numericValue =
                                                    Number(
                                                        value
                                                    ) ||
                                                    0;


                                                if (
                                                    numericValue >=
                                                    1000000
                                                ) {

                                                    return (
                                                        "₱" +
                                                        (
                                                            numericValue /
                                                            1000000
                                                        ).toFixed(
                                                            1
                                                        ) +
                                                        "M"
                                                    );
                                                }


                                                if (
                                                    numericValue >=
                                                    1000
                                                ) {

                                                    return (
                                                        "₱" +
                                                        (
                                                            numericValue /
                                                            1000
                                                        ).toFixed(
                                                            numericValue %
                                                            1000 ===
                                                            0
                                                                ? 0
                                                                : 1
                                                        ) +
                                                        "K"
                                                    );
                                                }


                                                return (
                                                    "₱" +
                                                    formatNumber(
                                                        numericValue
                                                    )
                                                );
                                            }
                                    }
                                }
                            },

                            plugins: {

                                legend: {
                                    display:
                                        false
                                },

                                tooltip: {

                                    displayColors:
                                        false,

                                    backgroundColor:
                                        "rgba(30, 43, 18, 0.95)",

                                    titleColor:
                                        "#ffffff",

                                    bodyColor:
                                        "#ffffff",

                                    padding:
                                        12,

                                    cornerRadius:
                                        9,

                                    callbacks: {

                                        title:
                                            function (
                                                items
                                            ) {

                                                if (
                                                    items.length ===
                                                    0
                                                ) {
                                                    return "";
                                                }


                                                return items[0]
                                                    .label;
                                            },

                                        label:
                                            function (
                                                context
                                            ) {

                                                return (
                                                    " Sales: " +
                                                    formatMoney(
                                                        context.raw
                                                    )
                                                );
                                            }
                                    }
                                }
                            }
                        }
                    }
                );


                /*
                 * hasTrendData is intentionally
                 * not used to hide the graph.
                 *
                 * A flat ₱0 line is still useful
                 * information for the selected
                 * period.
                 */

                void hasTrendData;

            } catch (error) {

                console.error(
                    "Dashboard Sales trend error:",
                    error
                );


                showTrendFallback(
                    "The Sales trend could not be displayed."
                );
            }
        }
    }
}

});