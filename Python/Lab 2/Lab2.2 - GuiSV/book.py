class Book:
    def __init__(self, book_id, title, author, pages, year_published, status, category):
        self.book_id = book_id
        self.title = title
        self.author = author
        self.pages = pages
        self.year_published = year_published
        self.status = status
        self.category = category

    def add_book(self, db):
        query = "INSERT INTO books (title, author, pages, year_published, status, category) VALUES (%s, %s, %s, %s, %s, %s)"
        params = (self.title, self.author, self.pages, self.year_published, self.status, self.category)
        db.execute_query(query, params)

    def update_book(self, db, new_title=None, new_author=None, new_pages=None, new_year_published=None, new_status=None, new_category=None):
        query = "UPDATE books SET "
        params = []
        updates = []

        if new_title:
            updates.append("title = %s")
            params.append(new_title)
        if new_author:
            updates.append("author = %s")
            params.append(new_author)
        if new_pages:
            updates.append("pages = %s")
            params.append(new_pages)
        if new_year_published:
            updates.append("year_published = %s")
            params.append(new_year_published)
        if new_status:
            updates.append("status = %s")
            params.append(new_status)
        if new_category:
            updates.append("category = %s")
            params.append(new_category)

        if updates:
            query += ", ".join(updates) + " WHERE book_id = %s"
            params.append(self.book_id)
            db.execute_query(query, tuple(params))

    def delete_book(self, db):
        query = "DELETE FROM books WHERE book_id = %s"
        db.execute_query(query, (self.book_id,))

    @staticmethod
    def search_book(db, book_id):
        query = "SELECT * FROM books WHERE book_id = %s"
        return db.fetch_one(query, (book_id,))

    @staticmethod
    def get_all_books(db):
        query = "SELECT * FROM books"
        return db.fetch_all(query)

    @staticmethod
    def search_book_by_title(db, title):
        query = "SELECT * FROM books WHERE title LIKE %s"
        return db.fetch_all(query, (f"%{title}%",))
