define([
    'lit',
    'lit/directives/live'
  ],
  function ({html, LitElement, nothing}, {live}) {
    class ElementKeyElement extends LitElement {
      static properties = {
        element: { type: Object, attribute: false },
        mode: { type: String },
      };

      constructor() {
        super();
      }

      createRenderRoot() {
        return this;
      }

      render() {
        if (!this.element) {
          return nothing;
        }
        return html`
          <input
            .value=${live(this.element?.key)}
            @change=${(e) => this.element.key = e.currentTarget.value}
            id="meta_key"
            class="form-control"
            required="required"
            ?readonly=${this.mode === 'edit'}
          />
        `
      }

      firstUpdated() {
        if (!this.element) {
          return;
        }
        if (this.element.key === '') {
          this.renderRoot.querySelector('input').focus();
        }
      }
    }

    window.customElements.define('mask-element-key', ElementKeyElement);

    return {ElementKeyElement}
  }
);
