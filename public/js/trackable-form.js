document.querySelectorAll('[data-alias-preview-url]').forEach((trackableForm) => {
    const initialName = trackableForm.querySelector('[name="name"]').value;
    const initialAlias = trackableForm.querySelector('[name="alias"]').value;
    let previewTimer;
    let previewVersion = 0;

    Vue.createApp({
        data() {
            return {
                name: initialName,
                alias: initialAlias,
                customAlias: initialAlias !== '',
            };
        },
        watch: {
            name() {
                this.previewAlias();
            },
        },
        mounted() {
            this.previewAlias();
        },
        methods: {
            editAlias(event) {
                this.customAlias = event.target.value !== '';
                this.previewAlias();
            },
            previewAlias() {
                clearTimeout(previewTimer);
                const version = ++previewVersion;

                if (this.customAlias) {
                    return;
                }

                // Leave generation to the backend if submitted before the preview arrives.
                this.alias = '';

                if (!this.name.trim()) {
                    return;
                }

                previewTimer = setTimeout(async () => {
                    const url = new URL(trackableForm.dataset.aliasPreviewUrl, window.location.origin);
                    url.searchParams.set('name', this.name);

                    try {
                        const response = await fetch(url, {
                            headers: { Accept: 'application/json' },
                        });

                        if (!response.ok) {
                            return;
                        }

                        const preview = await response.json();

                        if (version === previewVersion) {
                            this.alias = preview.alias;
                        }
                    } catch {
                        // A blank alias still uses the same generator when the form is saved.
                    }
                }, 150);
            },
        },
    }).mount(trackableForm);
});
