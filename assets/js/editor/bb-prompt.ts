import {openPrompt, TextAreaField} from "./prompt";
import {Node, NodeType} from "prosemirror-model";
import {EditorView} from "prosemirror-view";

export const bbPrompt = (node: NodeType, attrs, view: EditorView): void => openPrompt({
	title: "Bewerk bb",
	fields: {
		'bb': new TextAreaField({label: "Bb", value: attrs.bb})
	},
	callback(params) {
		view.dispatch(view.state.tr.replaceSelectionWith(node.createAndFill(params)))
		view.focus()
	}
})
