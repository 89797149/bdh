"use strict";
/*****
*sqlite 数据库操作类
*****/
const M = (tableName) => {
  return new Sqlite(tableName);
}


class Sqlite {
  constructor(tableName) {
    this.tableName = tableName;
    this.TableWhere = '';
    this.TableField = '';
    this.TableLimit = '';
    this.redatas = {};//存储结果对象
    this.TableOrder = '';
  }


  //字符串替换函数 ,
  delfh(str) {
    str = str.replace(",,", ",");
    if (str.substring(str.length - 1, str.length) == ",") {
      var str2 = str.substring(0, str.length - 1);
      this.delfh(str2);
    } else {
      var str2 = str;
    }
    return str2;
  }

  //返回结果
  FunResSQLite(obj) {
    console.log('结果');
    console.log(obj);
    return obj;
  }


  RunPHPFunc(objects) {
    return new Promise(function(resolve) {
      Ext.action(objects, function(data) {
        resolve(data);
      });
    });
  }


  //查询数据集
  async select() {

    let sqlQs = '';
    try {
      if (this.TableField.length > 0) {
        sqlQs += 'select ' + this.TableField + ' ';
      } else {
        sqlQs += 'select * '
      }
    } catch (e) {
      sqlQs += 'select * '
    }

    let sqls = sqlQs + this.pjSql();
    console.log(sqls)

    return await this.RunPHPFunc({
      'phpFunc': '__php_selectList',
      'param': [sqls]
    });



  }

  //删除
  async delete() {
    let sqlQs = '';
    try {
      sqlQs += 'delete ';
    } catch (e) {

    }

    let sqls = sqlQs + this.pjSql();
    console.log(sqls)

    return await this.RunPHPFunc({
      'phpFunc': '__php_dbQ',
      'param': [sqls]
    });
  }

  //更新
  async update(TableUpdate) {

    let setStr = '';

    try {
      if (TableUpdate) {

        //处理对象
        for (let key in TableUpdate) {

          setStr += key + '=' + TableUpdate[key].toString() + ',';
        }
        setStr = setStr.substring(0, setStr.length - 1);

      } else {
        console.log('修改数据不能为空');
      }
    } catch (e) {
      console.log(e);
    }




    let sqlQs = '';
    try {
      sqlQs += 'update ';
    } catch (e) {

    }

	let sqls = sqlQs + this.pjSql('where') + "set " + setStr; //不让where自动参与拼接 以下控制拼接


    try {
      if (this.TableWhere.length > 0) {
        sqls += ' where ' + this.TableWhere + ' ';
      }
    } catch (e) {

    }
	sqls = sqls.replace("from ", "")
    console.log(sqls)

    return await this.RunPHPFunc({
      'phpFunc': '__php_dbQ',
      'param': [sqls]
    });


  }

  //添加
  async insert(TableInsert) {
    let keys = '';
    let values = '';
    try {
      if (TableInsert) {

        //处理对象
        for (let key in TableInsert) {
          keys += key + ',';
          values += TableInsert[key].toString() + ',';
        }

      } else {
        console.log('添加数据不能为空');
      }
    } catch (e) {
      console.log(e);
    }

    let sqlQs = '';
    try {
      sqlQs += 'insert into ';
    } catch (e) {

    }


    let sqls = sqlQs + this.pjSql() + '(' + keys.substring(0, keys.length - 1) + ')' + ' VALUES ' + '(' + values.substring(0, values.length - 1) + ')';
    sqls = sqls.replace("from ", "");

    console.log(this.delfh(sqls))

    return await this.RunPHPFunc({
      'phpFunc': '__php_dbQ',
      'param': [sqls]
    });

  }

  //单条数据查询
  async find() {

    let sqlQs = '';
    try {
      if (this.TableField.length > 0) {
        sqlQs += 'select ' + this.TableField + ' ';
      } else {
        sqlQs += 'select * '
      }
    } catch (e) {
      sqlQs += 'select * '
    }

    let sqls = sqlQs + this.pjSql();
    console.log(sqls)

    return await this.RunPHPFunc({
      'phpFunc': '__php_db_find',
      'param': [sqls]
    });
  }

  // 获取数据条数
  async count() {
    let sqlQs = '';

    sqlQs += 'select count(*) '


    let sqls = sqlQs + this.pjSql();
    console.log(sqls)


    return await this.RunPHPFunc({
      'phpFunc': '__php_selectList',
      'param': [sqls]
    });
  }

  //原生sql执行
  async querySql(sql) {
    console.log(sql)

    return await this.RunPHPFunc({
      'phpFunc': '__php_dbQ',
      'param': [sql]
    });
  }


  //连贯方法操作----------------
  where(TableWhere) {
    this.TableWhere = TableWhere;
    return this;
  }

  field(TableField) {
    this.TableField = TableField;
    return this;
  }

  limit(TableLimit) {
    this.TableLimit = TableLimit.toString();
    return this;
  }

  order(TableOrder){
    this.TableOrder = TableOrder.toString();
    return this;
  }




  //拼接sql语句 调用PjSql时 传入对应sql关键字 即可避免参与拼接
  pjSql(ISWHERE) {

    let sqlQ = '';


    try {
      if (this.tableName.length > 0 && ISWHERE !== 'from') {
        // sqlQ += 'from ' + this.tableName + ' ';
        sqlQ += `from ${this.tableName} `;
      }
    } catch (e) {
      console.log("M()里字段必填");
      return false;
    }


    try {
      if (this.TableWhere.length > 0 && ISWHERE !== 'where') {
        // sqlQ += 'where ' + this.TableWhere + ' ';
        sqlQ += `where ${this.TableWhere} `;
      }
    } catch (e) {

    }

    try {
      if (this.TableOrder.length > 0 && ISWHERE !== 'order') {
        // sqlQ += 'order by ' + this.TableOrder + ' ';
          sqlQ += `order by ${this.TableOrder} `;
      }
    } catch (e) {

    }

    try {
      if (this.TableLimit.length > 0 && ISWHERE !== 'limit') {
        //sqlQ += 'limit ' + this.TableLimit + ' ';
        sqlQ += `limit ${this.TableLimit} `;
      }
    } catch (e) {

    }




    return sqlQ;
  }


};
/******
异步回调数据版本
  M('members').where(where).order('regdate desc').limit(listNum).select().then(e => {})




*****/
