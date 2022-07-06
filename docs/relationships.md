## Relationships

Of course, your database collections are probably related to one another. For example, a blog post may have many comments, or an order could be related to the user who placed it. Mongolid makes managing and working with these relationships easy. MongoDB and Mongolid in short supports four types of relationships:

> **Note:** MongoDB **relationships doesn't works like in a Relational database**. In MongoDB, data modeling decisions involve determining how to structure the documents to model the data effectively. The primary decision is whether to embed or to use references. See [MongoDB - Data Modeling Decisions](https://docs.mongodb.org/manual/core/data-model-design/) for more information on this subject.

Embedding relationship is when the embedded document does not have a collection to be saved in the database. 
So the model doesn't have a collection property defined. 
Upon retrieving this relationship, you will have a model filled with the data recorded in the parent document.

You can see how it works here:
- [Embeds](embeds.md)

---

In Mongolid a reference is made by storing the `_id` of the referenced object.

Referencing provides more flexibility than embedding;
however, to resolve the references, client-side applications must issue follow-up queries.
In other words, using references requires more roundtrips to the server.

You can see how it works here:
- [References](references.md)
