    <?php

    $salesFilterToday =
        date('Y-m-d');

    ?>


    <div
        class="modal fade"
        id="dateFilterModal"
        data-server-date="<?= htmlspecialchars(
            $salesFilterToday,
            ENT_QUOTES,
            'UTF-8'
        ) ?>">

    <div class="modal-dialog modal-dialog-centered">

    <div class="modal-content add-product-window">

    <div class="window-header">

    <h1>FILTER DATE</h1>

    <button
    type="button"
    class="close-btn"
    data-bs-dismiss="modal">

    ×

    </button>

    </div>

    <hr>

    <div class="mb-3">

    <label>START DATE</label>

    <input
    type="date"
    id="startDate"
    max="<?= htmlspecialchars(
        $salesFilterToday,
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
    class="form-control custom-input">

    </div>

    <div class="mb-4">

    <label>END DATE</label>

    <input
    type="date"
    id="endDate"
    max="<?= htmlspecialchars(
        $salesFilterToday,
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
    class="form-control custom-input">

    </div>

    <div class="d-flex justify-content-center gap-3">

    <button
    type="button"
    id="clearDateFilter"
    class="btn btn-light">

    CLEAR FILTER

    </button>

    <button
    type="button"
    id="applyDateFilter"
    class="btn-add-sales">

    APPLY

    </button>

    </div>

    </div>

    </div>

    </div>