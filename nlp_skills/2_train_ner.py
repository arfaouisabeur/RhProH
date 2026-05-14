"""
2_train_ner_v2.py
=================
Version améliorée de l'entraînement NER.
"""

import json
import random
from pathlib import Path

import spacy
from spacy.training import Example
from spacy.scorer import Scorer


def load_data(path, nlp):
    doc_bin = spacy.tokens.DocBin().from_disk(path)
    docs = list(doc_bin.get_docs(nlp.vocab))
    return [Example(nlp.make_doc(doc.text), doc) for doc in docs]


def evaluate(nlp, examples):
    scorer = Scorer()
    scored_examples = []

    for ex in examples:
        pred = nlp(ex.reference.text)
        scored_examples.append(Example(pred, ex.reference))

    scores = scorer.score(scored_examples)

    return (
        float(scores.get("ents_p", 0.0)),
        float(scores.get("ents_r", 0.0)),
        float(scores.get("ents_f", 0.0)),
    )

def train():
    print("=" * 60)
    print("  Entraînement amélioré du modèle NER")
    print("=" * 60)

    nlp = spacy.load("fr_core_news_sm")
    ner = nlp.get_pipe("ner") if "ner" in nlp.pipe_names else nlp.add_pipe("ner", last=True)
    ner.add_label("SKILL")

    print("\n→ Chargement des données...")
    train_examples = load_data("data/train.spacy", nlp)
    dev_examples = load_data("data/dev.spacy", nlp)
    print(f"  ✓ Train: {len(train_examples)} docs")
    print(f"  ✓ Dev:   {len(dev_examples)} docs")

    unaffected = [p for p in nlp.pipe_names if p != "ner"]
    output_dir = Path("models")
    output_dir.mkdir(exist_ok=True)
    best_dir = output_dir / "skill_ner_best"
    final_dir = output_dir / "skill_ner"
    history_path = output_dir / "training_history.json"

    best_f1 = -1.0
    patience = 7
    no_improve = 0
    history = []

    with nlp.disable_pipes(*unaffected):
        optimizer = nlp.resume_training()
        print("\n→ Démarrage de l'entraînement (max 40 itérations)...")
        for i in range(40):
            random.shuffle(train_examples)
            losses = {}
            for batch in spacy.util.minibatch(train_examples, size=8):
                nlp.update(batch, drop=0.2, losses=losses, sgd=optimizer)

            p, r, f1 = evaluate(nlp, dev_examples)
            loss_value = float(losses.get("ner", 0.0))
            history.append({
                "iter": int(i + 1),
                "loss": float(loss_value),
                "precision": float(p),
                "recall": float(r),
                "f1": float(f1),
            })

            print(f"  Iter {i+1:>2}/40 | Loss: {loss_value:.2f} | F1: {f1:.3f} | Prec: {p:.3f} | Rec: {r:.3f}")

            if f1 > best_f1:
                best_f1 = f1
                no_improve = 0
                nlp.to_disk(best_dir)
                print(f"  ★ Nouveau meilleur modèle sauvegardé (F1={best_f1:.3f})")
            else:
                no_improve += 1

            if no_improve >= patience:
                print(f"\n→ Early stopping déclenché après {patience} itérations sans amélioration.")
                break

    nlp.to_disk(final_dir)
    with open(history_path, "w", encoding="utf-8") as f:
        json.dump(history, f, indent=2, ensure_ascii=False)

    print(f"\n✓ Modèle final : {final_dir}")
    print(f"✓ Meilleur modèle : {best_dir} (F1={best_f1:.3f})")
    print(f"✓ Historique : {history_path}")


if __name__ == "__main__":
    train()
