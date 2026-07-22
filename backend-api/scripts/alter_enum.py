import sys
import os

# Add parent dir to path so we can import app
sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from sqlalchemy import text
from app.database.session import engine

def main():
    print("Modifying database column 'frequency' to include 'YEARLY'...")
    try:
        with engine.connect() as conn:
            # MySQL syntax for modifying enum
            conn.execute(text("ALTER TABLE loans MODIFY COLUMN frequency ENUM('WEEKLY', 'BIWEEKLY', 'MONTHLY', 'YEARLY') NOT NULL;"))
            conn.commit()
            print("Successfully updated ENUM column in database!")
    except Exception as e:
        print(f"Error updating ENUM: {e}")

if __name__ == "__main__":
    main()
