import mysql.connector

try:
    mydb = mysql.connector.connect(
        host="127.0.0.1",
        user="root",
        password="",
        database="ck_agent",
    )
    cursor = mydb.cursor()

    # Insert into sessions table
    cursor.execute("INSERT INTO sessions () VALUES ()")
    mydb.commit()
    message_id = cursor.lastrowid
    print("Session ID is:", message_id)

    # Insert into parameter_inputs table
    agent_id = 4
    cursor.execute("INSERT INTO parameter_inputs (agent_id) VALUES (%s)", (agent_id,))
    mydb.commit()
    print("Inserted agent_id into parameter_inputs with ID:", cursor.lastrowid)

finally:
    cursor.close()
    mydb.close()
