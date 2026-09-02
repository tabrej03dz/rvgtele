@once

<style>

    .demo-board {
        font-family:
            Inter,
            ui-sans-serif,
            system-ui,
            -apple-system,
            BlinkMacSystemFont,
            "Segoe UI",
            sans-serif;

        color: #172033;
    }


    .demo-shell {
        border: 1px solid #e7eaf0;
        border-radius: 14px;
        background: #fff;

        box-shadow:
            0 3px 12px
            rgba(15, 23, 42, .04);
    }


    /*
    |--------------------------------------------------------------------------
    | Stats
    |--------------------------------------------------------------------------
    */

    .demo-stat-grid {

        display: grid;

        grid-template-columns:
            repeat(
                4,
                minmax(
                    150px,
                    1fr
                )
            );

        gap: 10px;
    }


    .demo-stat {

        display: flex;

        align-items: center;

        gap: 11px;

        min-height: 82px;

        padding: 13px;

        border:
            1px solid
            #e7eaf0;

        border-radius:
            11px;

        background:
            #fff;
    }


    .demo-stat-icon {

        width: 40px;

        height: 40px;

        display: flex;

        align-items: center;

        justify-content: center;

        flex: 0 0 40px;

        border-radius: 50%;
    }


    .demo-stat-icon svg {

        width: 19px;

        height: 19px;
    }


    .demo-stat-label {

        font-size: 10px;

        font-weight: 800;

        color: #475467;
    }


    .demo-stat-value {

        margin-top: 3px;

        font-size: 21px;

        line-height: 1;

        font-weight: 900;

        color: #111827;
    }


    /*
    |--------------------------------------------------------------------------
    | City Cards
    |--------------------------------------------------------------------------
    */

    .demo-grid {

        display: grid;

        grid-template-columns:
            repeat(
                3,
                minmax(
                    260px,
                    1fr
                )
            );

        gap: 12px;
    }


    .city-card {

        overflow: hidden;

        border:
            1px solid
            #e5e7eb;

        border-top:
            3px solid
            #f5b900;

        border-radius:
            12px;

        background:
            #fff;

        box-shadow:
            0 2px 10px
            rgba(
                15,
                23,
                42,
                .04
            );
    }


    .city-head {

        padding:
            15px
            16px
            12px;

        border-bottom:
            1px solid
            #edf0f4;
    }


    .city-title {

        display: flex;

        align-items: center;

        gap: 8px;

        font-size:
            16px;

        font-weight:
            900;

        color:
            #111827;
    }


    .city-title svg {

        width:
            18px;

        height:
            18px;

        color:
            #d99b00;
    }


    .city-body {

        padding:
            13px;
    }


    /*
    |--------------------------------------------------------------------------
    | Preview
    |--------------------------------------------------------------------------
    */

    .media-preview-grid {

        display:
            grid;

        grid-template-columns:
            repeat(
                3,
                1fr
            );

        gap:
            6px;
    }


    .media-preview {

        height:
            82px;

        overflow:
            hidden;

        border:
            1px solid
            #e6e9ef;

        border-radius:
            8px;

        background:
            #f8fafc;
    }


    .media-preview img,
    .media-preview video {

        width:
            100%;

        height:
            100%;

        object-fit:
            cover;
    }


    .media-placeholder {

        display:
            flex;

        height:
            100%;

        align-items:
            center;

        justify-content:
            center;

        color:
            #94a3b8;
    }


    /*
    |--------------------------------------------------------------------------
    | Buttons
    |--------------------------------------------------------------------------
    */

    .demo-btn {

        display:
            inline-flex;

        min-height:
            38px;

        align-items:
            center;

        justify-content:
            center;

        gap:
            7px;

        border:
            1px solid
            #d8dee7;

        border-radius:
            9px;

        padding:
            0 13px;

        background:
            #fff;

        color:
            #475467;

        font-size:
            10px;

        font-weight:
            900;

        text-decoration:
            none;

        cursor:
            pointer;
    }


    .demo-btn svg {

        width:
            15px;

        height:
            15px;
    }


    .demo-btn-yellow {

        border-color:
            #f1b900;

        background:
            linear-gradient(
                180deg,
                #ffd22e 0%,
                #ffc400 100%
            );

        color:
            #171717;
    }


    .demo-btn-dark {

        border-color:
            #334155;

        background:
            #334155;

        color:
            #fff;
    }


    .demo-btn-green {

        border-color:
            #059669;

        background:
            #059669;

        color:
            #fff;
    }


    .demo-btn-red {

        border-color:
            #e11d48;

        background:
            #e11d48;

        color:
            #fff;
    }


    /*
    |--------------------------------------------------------------------------
    | Inputs
    |--------------------------------------------------------------------------
    */

    .demo-field label {

        display:
            block;

        margin-bottom:
            6px;

        color:
            #475569;

        font-size:
            10px;

        font-weight:
            900;
    }


    .demo-field input {

        width:
            100%;

        min-height:
            45px;

        border:
            1px solid
            #d7dde7;

        border-radius:
            10px;

        background:
            #fff;

        padding:
            0 12px;

        color:
            #1e293b;

        font-size:
            11px;

        outline:
            none;
    }


    .demo-field input:focus {

        border-color:
            #f1b900;

        box-shadow:
            0 0 0 3px
            rgba(
                241,
                185,
                0,
                .12
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Upload Box
    |--------------------------------------------------------------------------
    */

    .drop-box {

        border:
            1.5px dashed
            #e1b928;

        border-radius:
            13px;

        background:
            #fffaf0;

        padding:
            18px;

        text-align:
            center;
    }


    /*
    |--------------------------------------------------------------------------
    | Media Gallery
    |--------------------------------------------------------------------------
    */

    .media-grid {

        display:
            grid;

        grid-template-columns:
            repeat(
                4,
                minmax(
                    180px,
                    1fr
                )
            );

        gap:
            12px;
    }


    .media-card {

        overflow:
            hidden;

        border:
            1px solid
            #e5e7eb;

        border-radius:
            11px;

        background:
            #fff;
    }


    .media-box {

        height:
            170px;

        background:
            #f8fafc;
    }


    .media-box img,
    .media-box video {

        width:
            100%;

        height:
            100%;

        object-fit:
            cover;
    }


    .media-info {

        padding:
            10px;
    }


    .media-name {

        overflow:
            hidden;

        text-overflow:
            ellipsis;

        white-space:
            nowrap;

        font-size:
            10px;

        font-weight:
            800;

        color:
            #1f2937;
    }


    .media-meta {

        margin-top:
            4px;

        font-size:
            9px;

        color:
            #98a2b3;
    }


    /*
    |--------------------------------------------------------------------------
    | Responsive
    |--------------------------------------------------------------------------
    */

    @media (
        max-width:
        1100px
    ) {

        .demo-grid {

            grid-template-columns:
                repeat(
                    2,
                    1fr
                );
        }


        .media-grid {

            grid-template-columns:
                repeat(
                    3,
                    1fr
                );
        }
    }


    @media (
        max-width:
        700px
    ) {

        .demo-stat-grid,
        .demo-grid {

            grid-template-columns:
                repeat(
                    2,
                    1fr
                );
        }


        .media-grid {

            grid-template-columns:
                repeat(
                    2,
                    1fr
                );
        }
    }


    @media (
        max-width:
        480px
    ) {

        .demo-stat-grid,
        .demo-grid,
        .media-grid {

            grid-template-columns:
                1fr;
        }
    }

</style>

@endonce