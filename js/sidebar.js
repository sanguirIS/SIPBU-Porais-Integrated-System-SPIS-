/* ===========================
LOGOUT
=========================== */

(() => {

    const logoutBtn =
        document.getElementById("logoutBtn");

    if (
        !logoutBtn ||
        logoutBtn.dataset.logoutBound === "true"
    ) {
        return;
    }

    logoutBtn.dataset.logoutBound = "true";

    logoutBtn.addEventListener(
        "click",
        function (event) {

            event.preventDefault();

            Swal.fire({

                icon: "warning",

                title: "Log Out?",

                text: "Are you sure you want to log out?",

                showCancelButton: true,

                confirmButtonColor: "#B71C1C",

                cancelButtonColor: "#8CA15B",

                confirmButtonText: "Logout",

                cancelButtonText: "Cancel",

                customClass: {
                    popup: "system-logout-popup"
                }

            }).then((result) => {

                if (result.isConfirmed) {

                    window.location.href =
                        "/spis/logout/logout.php";

                }

            });

        }
    );

})();