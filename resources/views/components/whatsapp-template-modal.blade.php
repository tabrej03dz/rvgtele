<div
    id="whatsappTemplateModal"
    class="fixed inset-0 z-[9999] hidden"
>

    {{-- Overlay --}}
    <div
        class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
        onclick="closeWhatsappTemplateModal()"
    ></div>


    {{-- Modal --}}
    <div class="relative flex min-h-full items-center justify-center
                p-4">

        <div
            class="relative w-full max-w-lg overflow-hidden
                   rounded-3xl bg-white shadow-2xl"
        >

            {{-- Header --}}
            <div class="flex items-center justify-between
                        border-b border-slate-100 px-5 py-4">

                <div>

                    <div class="flex items-center gap-2">

                        <div
                            class="flex h-9 w-9 items-center
                                   justify-center rounded-xl
                                   bg-green-100 text-lg"
                        >
                            💬
                        </div>

                        <div>
                            <h2 class="font-bold text-slate-900">
                                Send WhatsApp
                            </h2>

                            <p
                                id="waLeadName"
                                class="text-xs text-slate-500"
                            ></p>
                        </div>

                    </div>

                </div>


                <button
                    type="button"
                    onclick="closeWhatsappTemplateModal()"
                    class="flex h-9 w-9 items-center justify-center
                           rounded-full bg-slate-100 text-xl
                           text-slate-500 hover:bg-slate-200"
                >
                    ×
                </button>

            </div>


            {{-- Loading --}}
            <div
                id="waTemplateLoading"
                class="p-10 text-center"
            >

                <div
                    class="mx-auto h-8 w-8 animate-spin rounded-full
                           border-4 border-green-100
                           border-t-green-600"
                ></div>

                <p class="mt-3 text-sm text-slate-500">
                    Loading templates...
                </p>

            </div>


            {{-- Content --}}
            <div
                id="waTemplateContent"
                class="hidden"
            >

                <div class="max-h-[70vh] overflow-y-auto p-5">

                    {{-- Lead --}}
                    <div
                        class="mb-4 rounded-xl border border-green-100
                               bg-green-50 p-3"
                    >

                        <div
                            id="waBusinessName"
                            class="font-semibold text-green-900"
                        ></div>

                        <div
                            id="waMobile"
                            class="mt-0.5 text-sm text-green-700"
                        ></div>

                    </div>


                    {{-- Templates --}}
                    <div>

                        <label
                            class="mb-2 block text-sm font-semibold
                                   text-slate-700"
                        >
                            Select Message Template
                        </label>

                        <div
                            id="waTemplateList"
                            class="space-y-2"
                        ></div>

                    </div>


                    {{-- Message --}}
                    <div class="mt-5">

                        <div class="mb-2 flex items-center justify-between">

                            <label
                                class="text-sm font-semibold text-slate-700"
                            >
                                Message
                            </label>

                            <span class="text-xs text-slate-400">
                                You can edit before sending
                            </span>

                        </div>

                        <textarea
                            id="waFinalMessage"
                            rows="8"
                            placeholder="Select template or type your message..."
                            class="w-full resize-y rounded-xl
                                   border border-slate-300 px-4 py-3
                                   text-sm leading-6 outline-none
                                   focus:border-green-500
                                   focus:ring-4 focus:ring-green-100"
                        ></textarea>

                    </div>

                </div>


                {{-- Footer --}}
                <div
                    class="flex items-center justify-between gap-3
                           border-t border-slate-100 bg-slate-50
                           px-5 py-4"
                >

                    <button
                        type="button"
                        onclick="closeWhatsappTemplateModal()"
                        class="rounded-xl border border-slate-300
                               bg-white px-4 py-2.5
                               text-sm font-semibold text-slate-600"
                    >
                        Cancel
                    </button>


                    <button
                        type="button"
                        onclick="sendWhatsappTemplateMessage()"
                        class="inline-flex items-center gap-2
                               rounded-xl bg-green-600
                               px-5 py-2.5 text-sm font-bold
                               text-white shadow-sm
                               hover:bg-green-700"
                    >
                        <span>WhatsApp</span>
                        <span>→</span>
                    </button>

                </div>

            </div>

        </div>

    </div>

</div>


<script>

let whatsappTemplates = [];
let whatsappNumber = '';
let selectedWhatsappTemplateId = null;


/*
|--------------------------------------------------------------------------
| Open Modal
|--------------------------------------------------------------------------
*/

async function openWhatsappTemplateModal(leadId) {

    const modal =
        document.getElementById('whatsappTemplateModal');

    const loading =
        document.getElementById('waTemplateLoading');

    const content =
        document.getElementById('waTemplateContent');

    const templateList =
        document.getElementById('waTemplateList');

    const message =
        document.getElementById('waFinalMessage');


    modal.classList.remove('hidden');

    loading.classList.remove('hidden');

    content.classList.add('hidden');

    templateList.innerHTML = '';

    message.value = '';

    selectedWhatsappTemplateId = null;


    try {

        const url =
            `{{ url('/leads') }}/${leadId}/whatsapp-templates`;

        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error('Unable to load templates.');
        }

        const data = await response.json();

        whatsappTemplates = data.templates || [];

        whatsappNumber =
            data.whatsapp_number || '';


        document.getElementById('waLeadName').textContent =
            data.lead?.name || 'Lead';

        document.getElementById('waBusinessName').textContent =
            data.lead?.business_name ||
            data.lead?.name ||
            'Lead';

        document.getElementById('waMobile').textContent =
            data.lead?.mobile || 'No mobile';


        renderWhatsappTemplates();

        loading.classList.add('hidden');
        content.classList.remove('hidden');

    } catch (error) {

        console.error(error);

        loading.innerHTML = `
            <div class="text-sm text-red-600">
                WhatsApp templates load nahi ho paaye.
            </div>
        `;
    }
}


/*
|--------------------------------------------------------------------------
| Render Templates
|--------------------------------------------------------------------------
*/

function renderWhatsappTemplates() {

    const container =
        document.getElementById('waTemplateList');

    container.innerHTML = '';


    /*
    |--------------------------------------------------------------------------
    | Custom Message
    |--------------------------------------------------------------------------
    */

    const custom = document.createElement('label');

    custom.className =
        'flex cursor-pointer items-start gap-3 rounded-xl ' +
        'border border-slate-200 p-3 hover:bg-slate-50';

    custom.innerHTML = `
        <input
            type="radio"
            name="wa_template"
            value="custom"
            class="mt-1 h-4 w-4 text-green-600"
        >

        <div>
            <div class="text-sm font-semibold text-slate-800">
                Custom Message
            </div>

            <div class="text-xs text-slate-500">
                Write message manually
            </div>
        </div>
    `;

    custom.addEventListener('click', function () {

        selectedWhatsappTemplateId = null;

        document.getElementById('waFinalMessage').value = '';

        setTimeout(() => {
            document.getElementById('waFinalMessage').focus();
        }, 50);
    });

    container.appendChild(custom);


    /*
    |--------------------------------------------------------------------------
    | Templates
    |--------------------------------------------------------------------------
    */

    whatsappTemplates.forEach(function (template) {

        const label =
            document.createElement('label');

        label.className =
            'flex cursor-pointer items-start gap-3 ' +
            'rounded-xl border border-slate-200 ' +
            'p-3 transition hover:border-green-300 ' +
            'hover:bg-green-50';


        label.innerHTML = `

            <input
                type="radio"
                name="wa_template"
                value="${template.id}"
                class="mt-1 h-4 w-4 text-green-600"
            >

            <div class="min-w-0 flex-1">

                <div class="flex items-center gap-2">

                    <div class="text-sm font-semibold text-slate-800">
                        ${escapeWhatsappHtml(template.name)}
                    </div>

                    <span
                        class="${
                            template.type === 'Global'
                                ? 'bg-purple-100 text-purple-700'
                                : 'bg-blue-100 text-blue-700'
                        }
                        rounded-full px-2 py-0.5 text-[10px]
                        font-semibold"
                    >
                        ${template.type}
                    </span>

                </div>

                <div class="mt-1 line-clamp-2 text-xs
                            leading-5 text-slate-500">
                    ${escapeWhatsappHtml(template.message)}
                </div>

            </div>
        `;


        label.addEventListener('click', function () {

            selectedWhatsappTemplateId =
                template.id;

            document.getElementById('waFinalMessage').value =
                template.message;
        });


        container.appendChild(label);
    });


    /*
    |--------------------------------------------------------------------------
    | No Template
    |--------------------------------------------------------------------------
    */

    if (whatsappTemplates.length === 0) {

        const empty =
            document.createElement('div');

        empty.className =
            'rounded-xl border border-dashed border-slate-300 ' +
            'bg-slate-50 p-4 text-center text-sm text-slate-500';

        empty.innerHTML = `
            Koi saved template available nahi hai.<br>
            Custom Message use kar sakte hain.
        `;

        container.appendChild(empty);
    }
}


/*
|--------------------------------------------------------------------------
| Send WhatsApp
|--------------------------------------------------------------------------
*/

function sendWhatsappTemplateMessage() {

    const message =
        document.getElementById('waFinalMessage').value.trim();

    if (!whatsappNumber) {

        alert('Lead ka valid mobile number available nahi hai.');

        return;
    }


    if (!message) {

        alert('Please message select ya enter karein.');

        document.getElementById('waFinalMessage').focus();

        return;
    }


    const url =
        'https://wa.me/' +
        whatsappNumber +
        '?text=' +
        encodeURIComponent(message);


    window.open(
        url,
        '_blank'
    );
}


/*
|--------------------------------------------------------------------------
| Close
|--------------------------------------------------------------------------
*/

function closeWhatsappTemplateModal() {

    document
        .getElementById('whatsappTemplateModal')
        .classList
        .add('hidden');
}


/*
|--------------------------------------------------------------------------
| Escape HTML
|--------------------------------------------------------------------------
*/

function escapeWhatsappHtml(text) {

    const div =
        document.createElement('div');

    div.textContent =
        text ?? '';

    return div.innerHTML;
}


/*
|--------------------------------------------------------------------------
| Escape Key
|--------------------------------------------------------------------------
*/

document.addEventListener('keydown', function (event) {

    if (event.key === 'Escape') {
        closeWhatsappTemplateModal();
    }

});

</script>
