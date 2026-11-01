<?php

include '../db.php';


$query =
mysqli_query(
    $conn,
    "
    SELECT
        price_per_hectare
    FROM rotor_price
    WHERE id = 1
    LIMIT 1
    "
);


$row =
$query
?
mysqli_fetch_assoc($query)
:
null;


$currentPrice =
$row['price_per_hectare']
??
'';

?>


<div
class="modal fade"
id="setPriceModal"
tabindex="-1"
aria-labelledby="setPriceModalTitle"
aria-hidden="true">


    <div
    class="modal-dialog modal-dialog-centered rotor-price-dialog">


        <div
        class="modal-content rotor-window rotor-price-window">


            <!-- HEADER -->

            <div class="rotor-price-header">


                <div class="rotor-price-heading">


                    <div class="rotor-price-icon">

                        <i class="bi bi-cash-stack"></i>

                    </div>


                    <div>

                        <h1 id="setPriceModalTitle">
                            SET ROTOR PRICE
                        </h1>

                        <p>
                            Set the service rate for every hectare.
                        </p>

                    </div>


                </div>


                <button
                type="button"
                class="rotor-close-btn"
                data-bs-dismiss="modal"
                aria-label="Close">

                    &times;

                </button>


            </div>


            <div class="rotor-price-divider"></div>


            <!-- FORM -->

            <form
            action="update/update_price_rotor.php"
            method="POST"
            id="rotorPriceForm">

                <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    $rentalCsrfToken,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>">


                <div class="rotor-price-body">


                    <div class="rotor-price-info">

                        <i class="bi bi-info-circle-fill"></i>

                        <span>
                            This rate will be used when computing new Rotor rentals.
                        </span>

                    </div>


                    <div class="rotor-price-field">


                        <label for="rotorPricePerHectare">
                            PRICE PER HECTARE
                        </label>


                        <div class="rotor-price-input-wrap">


                            <span class="rotor-price-prefix">
                                ₱
                            </span>


                            <input
                            type="number"
                            id="rotorPricePerHectare"
                            name="price_per_hectare"
                            step="0.01"
                            min="0.01"
                            class="rotor-price-input"
                            value="<?= htmlspecialchars(
                                (string)$currentPrice,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            placeholder="0.00"
                            required>


                            <span class="rotor-price-unit">
                                / hectare
                            </span>


                        </div>


                    </div>


                    <?php
                    if(
                        $currentPrice !== ''
                        &&
                        (float)$currentPrice > 0
                    ){
                    ?>


                        <div class="rotor-current-price">


                            <span>
                                Current Rate
                            </span>


                            <strong>

                                ₱<?= number_format(
                                    (float)$currentPrice,
                                    2
                                ) ?>

                                / hectare

                            </strong>


                        </div>


                    <?php
                    }
                    ?>


                </div>


                <div class="rotor-price-footer">


                    <button
                    type="button"
                    class="rotor-price-cancel-btn"
                    data-bs-dismiss="modal">

                        CANCEL

                    </button>


                    <button
                    type="submit"
                    class="rotor-price-save-btn">

                        <i class="bi bi-check2-circle"></i>

                        SAVE PRICE

                    </button>


                </div>


            </form>


        </div>


    </div>


</div>


<script>

const rotorPriceForm =
document.getElementById(
    "rotorPriceForm"
);


const rotorPriceInput =
document.getElementById(
    "rotorPricePerHectare"
);


const setPriceModal =
document.getElementById(
    "setPriceModal"
);


/* =========================================================
   SAVE PRICE
========================================================= */

rotorPriceForm
.addEventListener(
    "submit",
    function(e){

        e.preventDefault();


        Swal.fire({

            icon: "question",

            title: "Update Rotor Price?",

            text:
                "The new price will be used for future Rotor rental computations.",

            showCancelButton: true,

            confirmButtonText: "Yes, Update",

            cancelButtonText: "Cancel",

            reverseButtons: true,

            confirmButtonColor: "#8CA15B",

            cancelButtonColor: "#DC3545"

        })
        .then((result)=>{

            if(
                result.isConfirmed
            ){

                this.submit();

            }

        });

    }
);


/* =========================================================
   RESET UNSAVED PRICE WHEN MODAL CLOSES

   Example:
   Saved price = 4000

   User types 5000 but closes/cancels the modal.

   When the modal is opened again,
   the input returns to 4000.
========================================================= */

setPriceModal
.addEventListener(
    "hidden.bs.modal",
    function(){

        rotorPriceForm.reset();

    }
);

</script>